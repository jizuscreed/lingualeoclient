<?php
namespace LinguaLeoClient\LinguaLeoContent;

use LinguaLeoClient\Client;

class Translation
{
    public $translation_id = false;
    public $translation = false;
    public $votes = false;

    /**
     * @param $JSONObject
     * @param Client $client
     * @throws \LinguaLeoClient\Exception\ClientException
     */
    public function __construct($JSONObject, Client $client){
        $this->translation_id = $client->getFieldFromJsonObject($JSONObject, 'id');
        $this->translation = $client->getFieldFromJsonObject($JSONObject, 'value');
        $this->votes = $client->getFieldFromJsonObject($JSONObject, 'votes');
    }
}