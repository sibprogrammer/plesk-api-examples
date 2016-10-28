<?php
// Copyright 1999-2016. Parallels IP Holdings GmbH. All Rights Reserved.

/**
 * Client for Plesk API-RPC
 */
class PleskApiClient
{
    private $_host;
    private $_port;
    private $_protocol;
    private $_login;
    private $_password;
    private $_secretKey;

    /**
     * Create client
     *
     * @param string $host
     * @param int $port
     * @param string $protocol
     */
    public function __construct($host, $port = 8443, $protocol = 'https')
    {
        $this->_host = $host;
        $this->_port = $port;
        $this->_protocol = $protocol;
    }

    /**
     * Setup credentials for authentication
     *
     * @param string $login
     * @param string $password
     */
    public function setCredentials($login, $password)
    {
        $this->_login = $login;
        $this->_password = $password;
    }

    /**
     * Define secret key for alternative authentication
     *
     * @param string $secretKey
     */
    public function setSecretKey($secretKey)
    {
        $this->_secretKey = $secretKey;
    }

    /**
     * Perform API request
     *
     * @param string $request
     * @return string
     */
    public function request($request)
    {
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, "$this->_protocol://$this->_host:$this->_port/enterprise/control/agent.php");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->_getHeaders());
        curl_setopt($curl, CURLOPT_POSTFIELDS, $request);

        $result = curl_exec($curl);

        curl_close($curl);

        return $this->_handlingResponse($result);
    }

    /**
     * Retrieve list of headers needed for request
     *
     * @return array
     */
    private function _getHeaders()
    {
        $headers = array(
            "Content-Type: text/xml",
            "HTTP_PRETTY_PRINT: TRUE",
        );

        if ($this->_secretKey) {
            $headers[] = "KEY: $this->_secretKey";
        } else {
            $headers[] = "HTTP_AUTH_LOGIN: $this->_login";
            $headers[] = "HTTP_AUTH_PASSWD: $this->_password";
        }

        return $headers;
    }

    /**
     * Handling API response
     *
     * @param string $response
     * @return array
     */
    private function _handlingResponse($response)
    {
      $xml = simplexml_load_string($response);
      $json = json_encode($xml);
      $response = json_decode($json,TRUE);
      $status = array_search('status', $response);
      if ($status == 'ok')
      {
        return $response;
      }
      elseif($status == 'error')
      {
        $error_code = array_search('errcode', $response);
        $error = array_search('errtext', $response);
        throw new \Exception($error_code.':'.$error);
      }
      elseif(is_null($status))
      {
        throw new \Exception("No output from Plesk XML-RPC API.");
      }
    }

}
