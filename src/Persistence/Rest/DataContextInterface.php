<?php
namespace CFX\Rest;

interface DataContextInterface {
    public function getApiName();
    public function getApiVersion();
    public function getBaseUri();
    public function getApiKey();
    public function getApiKeySecret();
    public function getHttpClient();
}

