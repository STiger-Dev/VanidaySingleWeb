<?php

namespace BookneticApp\Backend\Appointments\Helpers;

use Zend\Http\Client as ZendClient;

/**
 * Class DexRequestObject
 * @package BookneticApp\Backend\Appointments\Helpers
 */
class DexRequestObject
{
    /**
     * API request URL
     */
    public $API_REQUEST_URI = 'https://dex.dev.vaniday.com';

    /**
     * @var ZendClient
     */
    public $zendClient;

    /**
     * Contructor
     */
    public function __construct() {
        $zendClient = new ZendClient();
        $this->zendClient = $zendClient;
    }

    /**
     *
     * @return void
     */
    public function addAppointment($params)
    {
        try {
            $reqGetUrl = $this->API_REQUEST_URI . "/web/appointments";
            $response = $this->doPostRequest($reqGetUrl, $params);
        } catch (\Exception $e) {
            
        }

        return;
    }

    /**
     *
     * @return void
     */
    public function deleteAppointment($id)
    {
        try {
            $reqDeleteUrl = $this->API_REQUEST_URI . "/web/appointments/{$id}";
            $response = $this->doDeleteRequest($reqDeleteUrl);
        } catch (\Exception $e) {
            
        }

        return;
    }

    /**
     *
     * @return void
     */
    public function addCustomer($params)
    {
        try {
            $reqGetUrl = $this->API_REQUEST_URI . "/web/customers";

            $response = $this->doPostRequest($reqGetUrl, $params);
        } catch (\Exception $e) {

        }

        return;
    }

    /**
     *
     * @return void
     */
    public function updateCustomer($id, $params)
    {
        try {
            $reqGetUrl = $this->API_REQUEST_URI . "/web/customers/{$id}";

            $response = $this->doPatchRequest($reqGetUrl, $params);
        } catch (\Exception $e) {
        }

        return;
    }

    /**
     *
     * @return void
     */
    public function deleteCustomer($id)
    {
        try {
            $reqDeleteUrl = $this->API_REQUEST_URI . "/web/customers/{$id}";

            $response = $this->doDeleteRequest($reqDeleteUrl);
        } catch (\Exception $e) {
        }

        return;
    }

    /**
     *
     * @return void
     */
    public function addLocation($params)
    {
        try {
            $reqGetUrl = $this->API_REQUEST_URI . "/web/location";

            $response = $this->doPostRequest($reqGetUrl, $params);
        } catch (\Exception $e) {

        }

        return;
    }

    /**
     *
     * @return void
     */
    public function updateLocation($id, $params)
    {
        try {
            $reqGetUrl = $this->API_REQUEST_URI . "/web/location/{$id}";

            $response = $this->doPatchRequest($reqGetUrl, $params);
        } catch (\Exception $e) {
        }

        return;
    }

    /**
     *
     * @return void
     */
    public function deleteLocation($id, $params)
    {
        try {
            $reqDeleteUrl = $this->API_REQUEST_URI . "/web/location/{$id}";

            $response = $this->doDeleteRequest($reqDeleteUrl, $params);
        } catch (\Exception $e) {
        }

        return;
    }

    /**
     *
     * @return void
     */
    public function addSales($params)
    {
        try {
            $reqGetUrl = $this->API_REQUEST_URI . "/web/sales";

            $response = $this->doPostRequest($reqGetUrl, $params);
        } catch (\Exception $e) {
        }

        return;
    }

    /**
     * Do API request with provided params
     * @param string $uri
     * @return Response
     */
    private function doDeleteRequest($uri, $params = array()){
        try
        {
            $this->zendClient->reset();
            $this->zendClient->setUri($uri);
            $this->zendClient->setMethod(\Zend\Http\Request::METHOD_DELETE);
            $this->zendClient->setHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ]);
            if (!empty ($params)) {
                $this->zendClient->setRawBody(json_encode($params));
            }
            $this->zendClient->send();
            $response = $this->zendClient->getResponse()->getContent();
        }
        catch (\Zend\Http\Exception\RuntimeException $runtimeException)
        {
            $this->_logger->info(__METHOD__ . '--- Request Exception: ' . $runtimeException->getMessage() );
            echo $runtimeException->getMessage();
        }

        return $response;
    }

    /**
     * Do API request with provided params
     * @param string $uri
     * @return Response
     */
    private function doPatchRequest($uri, $params){
        try
        {
            $this->zendClient->reset();
            $this->zendClient->setUri($uri);
            $this->zendClient->setMethod(\Zend\Http\Request::METHOD_PATCH);
            $this->zendClient->setHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ]);
            $this->zendClient->setRawBody(json_encode($params));
            $this->zendClient->send();
            $response = $this->zendClient->getResponse()->getContent();
        }
        catch (\Zend\Http\Exception\RuntimeException $runtimeException)
        {
            echo $runtimeException->getMessage();
        }

        return $response;
    }

    /**
     * Do API request with provided params
     * @param string $uri
     * @return Response
     */
    private function doGetRequest($uri){
        try
        {
            $this->zendClient->reset();
            $this->zendClient->setUri($uri);
            $this->zendClient->setMethod(\Zend\Http\Request::METHOD_GET);
            $this->zendClient->setHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ]);
            $this->zendClient->send();
            $response = $this->zendClient->getResponse()->getContent();
        }
        catch (\Zend\Http\Exception\RuntimeException $runtimeException)
        {
            echo $runtimeException->getMessage();
        }

        return $response;
    }

    /**
     * Do API request with provided params
     * @param string $uri
     * @return Response
     */
    private function doPostRequest($uri, $params){
        try
        {
            $this->zendClient->reset();
            $this->zendClient->setUri($uri);
            $this->zendClient->setMethod(\Zend\Http\Request::METHOD_POST);
            $this->zendClient->setRawBody(json_encode($params));
            $this->zendClient->send();
            $response = $this->zendClient->getResponse()->getContent();
        }
        catch (\Zend\Http\Exception\RuntimeException $runtimeException)
        {
            echo $runtimeException->getMessage();
        }

        return $response;
    }
}
