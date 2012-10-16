<?php

namespace Artax\Http;

class StdResponse extends StdMessage implements Response {

    /**
     * @var int
     */
    protected $statusCode = 200;

    /**
     * @var string
     */
    protected $statusDescription;

    /**
     * @var bool
     */
    protected $wasSent = false;
    
    /**
     * @return string
     */
    public function __toString() {
        $msg = $this->getStartLine() . "\r\n";
        $msg.= $this->getRawHeaders();
        $msg.= "\r\n";
        $msg.= $this->getBody();
        
        return $msg;
    }

    /**
     * @return int
     */
    public function getStatusCode() {
        return $this->statusCode;
    }

    /**
     * @param string $httpStatusCode
     * @return void
     */
    public function setStatusCode($httpStatusCode) {
        $this->statusCode = (int) $httpStatusCode;
    }

    /**
     * @return string
     */
    public function getStatusDescription() {
        return $this->statusDescription;
    }

    /**
     * @param string $httpStatusDescription
     * @return void
     */
    public function setStatusDescription($httpStatusDescription) {
        $this->statusDescription = $httpStatusDescription;
    }

    /**
     * Build a raw HTTP response start line (without trailing CRLF)
     * 
     * @return string
     */
    public function getStartLine() {
        return "HTTP/{$this->httpVersion} {$this->statusCode} {$this->statusDescription}";
    }
    
    /**
     * Get the raw HTTP response data up to and including the terminating header CRLFs
     * 
     * @return string
     */
    public function getRawStartLineAndHeaders() {
        $msg = $this->getStartLine() . "\r\n";
        $msg.= $this->getRawHeaders();
        $msg.= "\r\n";
        
        return $msg;
    }
    
    /**
     * Parses and assigns start line values according to RFC 2616 Section 6.1
     * 
     * @param string $rawStartLineStr
     * @return void
     * @throws HttpException
     */
    public function setStartLine($rawStartLineStr) {
        $startLinePattern = ',^HTTP/(\d+\.\d+) (\d{3}) (.+)$,';
        
        if (!preg_match($startLinePattern, trim($rawStartLineStr), $match)) {
            throw new HttpException(
                'Invalid HTTP start line: ' . $rawStartLineStr
            );
        }
        
        $this->httpVersion = $match[1];
        $this->statusCode = $match[2];
        $this->statusDescription = $match[3];
    }

    /**
     * Has this response been sent?
     * 
     * @return bool
     */
    public function wasSent() {
        return $this->wasSent;
    }

    /**
     * Formats/sends all headers and the response message entity body.
     * 
     * @return void
     */
    public function send() {
        $this->normalizeHeadersForSend();
        $this->sendHeaders();
        
        if (!empty($this->body)) {
            $this->sendBody();
        }
        
        $this->wasSent = true;
    }
    
    protected function normalizeHeadersForSend() {
        if ($this->getBodyStream()) {
            $this->setHeader('Transfer-Encoding', 'chunked');
            $this->removeHeader('Content-Length');
        } elseif ($this->body) {
            $this->setHeader('Content-Length', strlen($this->body));
            $this->removeHeader('Transfer-Encoding');
        } else {
            $this->removeHeader('Content-Length');
            $this->removeHeader('Transfer-Encoding');
        }
    }
    
    protected function sendHeaders() {
        header($this->getStartLine());
        foreach ($this->headers as $header) {
            $header->send();
        }
        flush();
    }
    
    protected function sendBody() {
        $entityBodyStream = $this->getBodyStream();
        if (empty($entityBodyStream)) {
            echo $this->body;
            return;
        }
        
        while (!feof($entityBodyStream)) {
            if ($bodyChunk = @fread($this->body, 4096)) {
                $chunkLength = strlen($bodyChunk);
                echo dechex($chunkLength) . "\r\n$bodyChunk\r\n";
                flush();
            }
        }
        
        echo "0\r\n\r\n";
    }
}
