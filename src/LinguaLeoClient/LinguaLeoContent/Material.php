<?php
namespace LinguaLeoClient\LinguaLeoContent;

use LinguaLeoClient\Exception\ClientException;
use LinguaLeoClient\Client;

class Material {

    public $material_id = false;
    public $title = false;
    public $format = false;
    public $level = false;
    public $create_date = false;
    public $preview = false;

    public $client = false;

    public function __construct($JSONObject, Client $client){
        $this->client = $client;

        // fill object fields
        $this->material_id = $client->getFieldFromJsonObject($JSONObject, 'id');
        $this->title = $client->getFieldFromJsonObject($JSONObject, 'content_name');
        $this->format = $client->getFieldFromJsonObject($JSONObject, 'format');
        $this->level = $client->getFieldFromJsonObject($JSONObject, 'level');
        $this->create_date = $client->getFieldFromJsonObject($JSONObject, 'cdate');
        $this->preview = $client->getFieldFromJsonObject($JSONObject, 'first_page');
    }

    /**
     * @return string|\string[]
     */
    public function getFullText(){
        return $this->client->getMaterialFullText($this->material_id);
    }
}
?>