<?php
namespace LinguaLeoClient\LinguaLeoContent;

use LinguaLeoClient\Client;
use LinguaLeoClient\Exception\ClientException;

class Collection {
    // url to get collection contents
    public $find_url = false;

    public $title = false;
    public $description = false;
    public $picture_url = false;

    /**
     * @param $JSONObject
     * @param Client $client
     * @throws ClientException
     */
    public function __construct($JSONObject, Client $client){
        $this->title = $client->getFieldFromJsonObject($JSONObject, 'title');
        $this->description = $client->getFieldFromJsonObject($JSONObject, 'descr');
        $this->picture_url = $client->getFieldFromJsonObject($JSONObject, 'pic_url');
        // find_url field
        $this->find_url = str_replace($client->getLinguaLeoApiHost(), '', urldecode($client->getFieldFromJsonObject($JSONObject, 'find_url')));
        // fix (for some reason lingualeo api returns URL with lost ampersand between two parameters)
        $this->find_url = str_replace('3version', '3&version', $this->find_url);
    }
}
?>