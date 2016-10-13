<?php
namespace LinguaLeoClient\LinguaLeoContent;

use LinguaLeoClient\Client;
use LinguaLeoClient\Exception\ClientException;

class User{
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
     * @param $JSONObject
     * @param Client $client
     * @throws ClientException
     */
    public function __construct($JSONObject, Client $client){
        $this->user_id = $client->getFieldFromJsonObject($JSONObject, 'user_id');
        $this->nickname = $client->getFieldFromJsonObject($JSONObject, 'nickname');
        $this->avatar_url = $client->getFieldFromJsonObject($JSONObject, 'avatar');
        $this->fullname = $client->getFieldFromJsonObject($JSONObject, 'fullname');
        $this->age = $client->getFieldFromJsonObject($JSONObject, 'age');
        $this->level = $client->getFieldFromJsonObject($JSONObject, 'xp_level');
        $this->is_gold = $client->getFieldFromJsonObject($JSONObject, 'is_gold');
        $this->meatballs = $client->getFieldFromJsonObject($JSONObject, 'meatballs');
        $this->dateOfBirth = $client->getFieldFromJsonObject($JSONObject, 'birth');
        $this->dictionaryLength = $client->getFieldFromJsonObject($JSONObject, 'words_cnt');
        $this->vocabularLength = $client->getFieldFromJsonObject($JSONObject, 'words_known');
    }
}
?>