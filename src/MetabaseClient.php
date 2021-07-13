<?php


namespace LuisJuncalDev\MetabasePHPApi;


class MetabaseClient {

    private $url;
    private $key;

    public function __construct($url, $key)
    {
        $this->url = $url;
        $this->key = $key;
    }


}