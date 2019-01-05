<?php
namespace LinguaLeoClient\LinguaLeoContent;

use LinguaLeoClient\Client;

class Word
{
    const LEARNING_STATE_UNLEARNED = 1023;
    const LEARNING_STATE_LEARNED_PERCENTS_25 = 1019;
    const LEARNING_STATE_LEARNED_PERCENTS_50 = 1017;
    const LEARNING_STATE_LEARNED_PERCENTS_75 = 1009;
    const LEARNING_STATE_LEARNED = 1;

    public $word_id = false;
    public $word = false;
    /**
     * какая-то внутрення хрень lingualeo - видимо для различения слов, фраз и прочего
     * @var bool|mixed
     */
    public $word_type = false;
    public $translation_id = false;
    public $translation_text = false;

    public $training_state_code = false;

    /**
     * @param $JSONObject
     * @param Client $client
     * @throws \LinguaLeoClient\Exception\ClientException
     */
    public function __construct($JSONObject, Client $client){
        $this->word_id = $client->getFieldFromJsonObject($JSONObject, 'word_id');
        $this->word = $client->getFieldFromJsonObject($JSONObject, 'word_value');
        $this->word_type = $client->getFieldFromJsonObject($JSONObject, 'word_type');
        $this->translation_id = $client->getFieldFromJsonObject($JSONObject, 'translate_id');
        $this->translation_text = $client->getFieldFromJsonObject($JSONObject, 'translate_value');
        $this->training_state_code = $client->getFieldFromJsonObject($JSONObject, 'training_state');
    }

    /**
     * проверяем, изучено ли слово (нужна отдельная функция из-за страшного бардака в кодах у лингва лео)
     */
    public function isLearned()
    {
        if($this->training_state_code === self::LEARNING_STATE_LEARNED){
            // конкретный статус "изучено"
            return true;
        } elseif($this->training_state_code === self::LEARNING_STATE_UNLEARNED){
            // конкретный статус "неизучено"
            return false;
        } elseif($this->training_state_code === self::LEARNING_STATE_LEARNED_PERCENTS_25 || $this->training_state_code === self::LEARNING_STATE_LEARNED_PERCENTS_50 || $this->training_state_code === self::LEARNING_STATE_LEARNED_PERCENTS_75){
            // конкретный статус "в процессе"
            return false;
        } else {
            // конкретного статуса нет, предполагаем, что изучено
            return true;
        }
    }
}