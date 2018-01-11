<?php
namespace CFX\Persistence\Test;

class HttpClient extends \GuzzleHttp\Client {
    protected $nextResponse = [];
    protected $requestTrace = [];

    public function setNextResponse(\Psr\Http\Message\ResponseInterface $r) {
        $this->nextResponse[] = $r;
        return $this;
    }

    public function getLastRequest() {
        $i = count($this->requestTrace);
        if ($i == 0) throw new \RuntimeException("There are no more queued HTTP Requests, but you've tried to view the last response. This may indicate that there's something wrong in your test or in your application code.");
        return array_pop($this->requestTrace);
    }

    public function send(\Psr\Http\Message\RequestInterface $r, array $options = []) {
        if (count($this->nextResponse) == 0) throw new \RuntimeException("This is a test HTTP Client that does not make real HTTP calls. You must set the response for the request you're about to execute (`{$r->getMethod()} {$r->getUri()}`) by using the `setNextResponse(\GuzzleHttp\Message\ResponseInterface \$r)` method.");

        $this->requestTrace[] = $r;
        $res = array_pop($this->nextResponse);
        return $res;
    }
}

