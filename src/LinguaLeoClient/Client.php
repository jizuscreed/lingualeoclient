<?php
namespace LinguaLeoClient;

use GuzzleHttp\Client as GuzzleClient;

use LinguaLeoClient\Exception\LinguaLeoException;
use LinguaLeoClient\Exception\ClientException;
use LinguaLeoClient\LinguaLeoContent\User;
use LinguaLeoClient\LinguaLeoContent\Collection;
use LinguaLeoClient\LinguaLeoContent\Material;
use LinguaLeoClient\LinguaLeoContent\Word;
use LinguaLeoClient\LinguaLeoContent\Translation;


class Client {

    /**
     * @var bool|GuzzleClient
     */
    private $httpClient = false;
    private $LinguaLeoApiHost = 'http://api.lingualeo.com';
    private $userAgent = 'LinguaLeo v2.4.4 (samsung SCH-535; Android 4.4.2; en_US; 63;)';

    public $signedup = false;
    /**
     * @var bool|\LinguaLeoClient\LinguaLeoContent\User
     */
    public $user = false;
    /**
     * @var bool
     */
    public $hasNextDictionaryChunk = true;

    public function __construct($email, $password){
        $this->createHttpClient();
        $this->login($email, $password);
    }

    /**
     * @return string
     */
    public function getLinguaLeoApiHost(){
        return $this->LinguaLeoApiHost;
    }

    /**
     * @throws Exception\ClientException
     */
    private function createHttpClient(){
        try {
            $this->httpClient = new GuzzleClient([
                'base_uri' => $this->LinguaLeoApiHost,
                'cookies' => true,
                'headers' => [
                    'User-Agent' => $this->userAgent
                ]
            ]);
        } catch (\Exception $e){
            throw new ClientException($e->getMessage(), $e->getCode());
        }
    }

    /**
     * @param string $email
     * @param string $password
     * @return User
     * @throws Exception\LinguaLeoException
     * @throws Exception\ClientException
     */
    private function login($email, $password){
        if($this->signedup){
            // уже авторизованы
            throw new ClientException("Client already signed up (Клиент уже авторизован)");
        }

        try{ // пытаемся авторизоваться
            $result = $this->getJsonResponse('Login?port=3&email='.$email.'&password='.$password.'&refcode=');

            $this->signedup = true;
            $this->user = new User($result->user, $this);
        } catch(ClientException $e){
            // 
        }

        return $this->user;
    }

    /**
     * @return Collection[]|bool
     * @throws ClientException
     * @throws LinguaLeoException
     */
    public function getCollections(){
        $this->checkSignUp();

        $result = $this->getJsonResponse('/collection?&formats=1,3&version=1&offlineAllowed=1&port=3');

        if(isset($result->collection)){
            $collections = [];
            foreach($result->collection as $collectionJSON){
                $collections[] = new Collection($collectionJSON, $this);
            }
            return $collections;
        } else {
            return [];
        }
    }

    /**
     * @param Collection $collection
     * @param int $chunk_offset
     * @param int $chunk_limit
     * @return Material[]
     * @throws Exception\ClientException
     */
    public function getMaterialsFromCollection(Collection $collection, $chunk_offset = 0, $chunk_limit = 0){
        $this->checkSignUp();

        $materials = [];
            // chunk size is 30 items
            $chunk_id = $chunk_offset;
            $added_chunks = 0;
            while($added_chunks < $chunk_limit || $added_chunks == 0){
                $chunk_id++;
                $result = $this->getJsonResponse($collection->find_url.'&chunk='.$chunk_id);
                // Set chunk limit if selection all
                if($added_chunks == 0 && $chunk_limit == 0){
                    $chunk_limit = $result->chunk->total;
                }
                // Detect end of chunks
                $added_chunks++;
                if($result->chunk->current >= $result->chunk->total){
                    $chunk_limit = $added_chunks;
                }

                if(isset($result->content)){
                    // processing chunks
                    foreach($result->content as $material_json){
                        $materials[] = new Material($material_json, $this);
                    }
                }
            }

        return $materials;
    }

    /**
     * @param $material_id
     * @param bool $as_pages
     * @return string[]|string
     */
    public function getMaterialFullText($material_id, $as_pages = false){
        $this->checkSignUp();

        $result = $this->getJsonResponse('/content/'.$material_id.'/page/all?port=3');

        if($as_pages){
            // return array of pages
            $pages = [];
            foreach($result->page as $JSONPage){
                $pages[] = $JSONPage->text.' ';
            }
            return $pages;
        } else {
            // return full text as string
            $full_text = '';
            foreach($result->page as $JSONPage){
                $full_text .= $JSONPage->text.' ';
            }
            return $full_text;
        }
    }

    /**
     * @param int $words_offset
     * @param int $chunk_limit
     * @return Word[]
     */
    public function getDictionary($words_offset = 0, $chunk_limit = 0, $onlyWords = false){
        $this->checkSignUp();
        // проставляем, получать из словаря только слова или ещё и фразы и прочее
        $port = $onlyWords?3:1;

        $added_words = $words_offset;
        $added_chunks = 0;
        $result = false;
        $words = [];

        while($added_chunks === 0 || ($result->next_chunk && ($chunk_limit == 0 || $added_chunks < $chunk_limit))){
            $result = $this->getJsonResponse('/Userdict?port='.$port.'&offset='.$added_words);

            $added_words += count($result->words);
            foreach($result->words as $JSONWord){
                $words[] = new Word($JSONWord, $this);
            }
            $added_chunks++;
        }

        // проставляем, есть ли следующий кусок данных
        $this->hasNextDictionaryChunk = $result->next_chunk;

        return $words;
    }

    /**
     * @param string $word
     * @return Translation[]
     */
    public function getWordTranslations($word){
        $this->checkSignUp();

        $result = $this->getJsonResponse('/Gettranslates?port=3&noauth=1&word='.strtolower($word));
        $translations = [];
        foreach($result->translate as $translationJSON){
            $translations[] = new Translation($translationJSON, $this);
        }

        return $translations;
    }
    
    /**
     * @param $JSONObject
     * @param $field_name
     * @return mixed
     * @throws Exception\ClientException
     */
    public function getFieldFromJsonObject($JSONObject, $field_name){
        if(isset($JSONObject->$field_name)){
            return $JSONObject->$field_name;
        } else {
            throw new ClientException('No "$field_name" field in json object (Нет поля "$field_name" в json обьекте)');
        }
    }

    /**
     * @param string $url
     * @return mixed
     * @throws Exception\LinguaLeoException
     * @throws ClientException;
     */
    private function getJsonResponse($url){
        try {
            $response = $this->httpClient->get($url);
            $JSONResult = json_decode($response->getBody()->getContents());
        } catch(\Exception $e){
            throw new ClientException($e->getMessage(), $e->getCode());
        }

        if(isset($JSONResult->error_msg) && $JSONResult->error_msg != ''){
            throw new LinguaLeoException($JSONResult->error_msg, $JSONResult->error_code);
        }

        return $JSONResult;
    }

    /**
     * @throws Exception\ClientException
     */
    private function checkSignUp(){
        if(!$this->signedup){
            throw new ClientException('Trying to get data on unathorized client (Попытка получения данных из неавторизованного клиента)');
        }
    }
}

?>