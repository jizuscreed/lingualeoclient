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

    /**
     * Client constructor.
     * @param $email
     * @param $password
     * @throws ClientException
     * @throws LinguaLeoException
     */
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
            throw $e;
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
     * @param int $chunkOffset
     * @param int $chunkLimit
     * @return Material[]
     * @throws ClientException
     * @throws LinguaLeoException
     */
    public function getMaterialsFromCollection(Collection $collection, $chunkOffset = 0, $chunkLimit = 0){
        $this->checkSignUp();

        $materials = [];
            // chunk size is 30 items
            $chunk_id = $chunkOffset;
            $added_chunks = 0;
            while($added_chunks < $chunkLimit || $added_chunks == 0){
                $chunk_id++;
                $result = $this->getJsonResponse($collection->find_url.'&chunk='.$chunk_id);
                // Set chunk limit if selection all
                if($added_chunks == 0 && $chunkLimit == 0){
                    $chunkLimit = $result->chunk->total;
                }
                // Detect end of chunks
                $added_chunks++;
                if($result->chunk->current >= $result->chunk->total){
                    $chunkLimit = $added_chunks;
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
     * @param $materialId
     * @param bool $asPages
     * @return string[]|string
     * @throws ClientException
     * @throws LinguaLeoException
     */
    public function getMaterialFullText($materialId, $asPages = false){
        $this->checkSignUp();

        $result = $this->getJsonResponse('/content/'.$materialId.'/page/all?port=3');

        if($asPages){
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
     * Getting chunk of users dictionary
     * @param int $wordsOffset
     * @param int $chunkLimit how many chunks we going to grab (0 for whole dictinary). Chunk size is 30
     * @param bool $onlyWords получать только слова (или и слова и фразы)
     * @return LinguaLeoContent\Word[]
     * @throws ClientException
     * @throws LinguaLeoException
     */
    public function getDictionary($wordsOffset = 0, $chunkLimit = 0, $onlyWords = false){
        $this->checkSignUp();
        // проставляем, получать из словаря только слова или ещё и фразы и прочее 
        $port = $onlyWords ? 3 : 1;

        $addedWords = $wordsOffset;
        $addedChunks = 0;
        $result = false;
        $words = [];

        while($addedChunks === 0 || ($result->next_chunk && ($chunkLimit == 0 || $addedChunks < $chunkLimit))){
            $result = $this->getJsonResponse('/Userdict?port='.$port.'&offset='.$addedWords);

            $addedWords += count($result->words);
            foreach($result->words as $JSONWord){
                $words[] = new Word($JSONWord, $this);
            }
            $addedChunks++;
        }

        // проставляем, есть ли следующий кусок данных
        $this->hasNextDictionaryChunk = $result->next_chunk;

        return $words;
    }

    /**
     * @param string $word
     * @return Translation[]
     * @throws ClientException
     * @throws LinguaLeoException
     */
    public function getWordTranslations($word){
        $this->checkSignUp();

        $result = $this->getJsonResponse('/Gettranslates?port=3&noauth=1&word='.strtolower($word));
        $translations = [];

        if(isset($result->translate)){ // перевода почему то может и не быть по каким то непонятным причинам
            foreach($result->translate as $translationJSON){
                $translations[] = new Translation($translationJSON, $this);
            }
        }

        return $translations;
    }
    
    /**
     * @param $JsonObject
     * @param $fieldName
     * @return mixed
     * @throws Exception\ClientException
     */
    public function getFieldFromJsonObject($JsonObject, $fieldName){
        if(isset($JsonObject->$fieldName)){
            return $JsonObject->$fieldName;
        } else {
            throw new ClientException("No \"{$fieldName}\" field in json object (Нет поля \"{$fieldName}\" в json обьекте)");
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
            $JsonResult = json_decode($response->getBody()->getContents());
        } catch(\Exception $e){
            throw new ClientException($e->getMessage(), $e->getCode());
        }

        if(isset($JsonResult->error_msg) && $JsonResult->error_msg != ''){
            throw new LinguaLeoException($JsonResult->error_msg, $JsonResult->error_code);
        }

        return $JsonResult;
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