<?php
namespace LinguaLeoClient\LinguaLeoContent;

use LinguaLeoClient\Client;
use LinguaLeoClient\Exception\ClientException;

class User
{
    public $user_id = false;
    public $nickname = false;
    public $avatar_url = false;
    public $fullname = false;
    public $age = false;
    public $level = false;
    public $is_gold = false;
    public $meatballs = false;
    public $dateOfBirth = false;

    public $dictionaryLength = false;
    public $vocabularLength = false;

    /**
     * @param $JsonObject
     * @param Client $client
     * @throws ClientException
     */
    public function __construct($JsonObject, Client $client){
        $this->user_id = $client->getFieldFromJsonObject($JsonObject, 'user_id');
        $this->nickname = $client->getFieldFromJsonObject($JsonObject, 'nickname');
        $this->avatar_url = $client->getFieldFromJsonObject($JsonObject, 'avatar');
        $this->fullname = $client->getFieldFromJsonObject($JsonObject, 'fullname');
        $this->age = $client->getFieldFromJsonObject($JsonObject, 'age');
        $this->level = $client->getFieldFromJsonObject($JsonObject, 'xp_level');
        $this->is_gold = $client->getFieldFromJsonObject($JsonObject, 'is_gold');
        $this->meatballs = $client->getFieldFromJsonObject($JsonObject, 'meatballs');
        $this->dateOfBirth = $client->getFieldFromJsonObject($JsonObject, 'birth');
        $this->dictionaryLength = $client->getFieldFromJsonObject($JsonObject, 'words_cnt');
        $this->vocabularLength = $client->getFieldFromJsonObject($JsonObject, 'words_known');
    }
}