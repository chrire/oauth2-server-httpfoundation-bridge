<?php

namespace OAuth2\HttpFoundationBridge;

use Symfony\Component\HttpFoundation\Request as BaseRequest;
use OAuth2\RequestInterface;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 *
 */
 class Request extends BaseRequest implements RequestInterface
 {
     /**
      * Initializes the request with values given as parameters.
      * Overwrite to fix an apache header bug. Read more here:
      * http://stackoverflow.com/questions/11990388/request-headers-bag-is-missing-authorization-header-in-symfony-2%E2%80%94
      *
      * @return Request A new request
      *
      * @api
      */
     public function initialize(array $query = array(), array $request = array(), array $attributes = array(), array $cookies = array(), array $files = array(), array $server = array(), $content = null)
     {
         parent::initialize($query, $request, $attributes, $cookies, $files, $server, $content);
         // fix the bug.
         self::fixAuthHeader($this->headers);
     }

     public function query($name, $default = null)
     {
         return $this->query->get($name, $default);
     }

     public function request($name, $default = null)
     {
         return $this->request->get($name, $default);
     }

     public function server($name, $default = null)
     {
         return $this->server->get($name, $default);
     }

     public function headers($name, $default = null)
     {
         return $this->headers->get($name, $default);
     }

     public function getAllQueryParameters()
     {
         return $this->query->all();
     }

     public static function createFromRequest(BaseRequest $request)
     {
         return new static($request->query->all(), $request->request->all(), $request->attributes->all(), $request->cookies->all(), $request->files->all(), $request->server->all(), $request->getContent());
     }

     public static function createFromRequestStack(RequestStack $request)
     {
         $request = $request->getCurrentRequest();
         return self::createFromRequest($request);
     }
    
     public static function createFromGlobals()
     {
         return parent::createFromGlobals();
     }
    
     /**
      * PHP does not include HTTP_AUTHORIZATION in the $_SERVER array, so this header is missing.
      * We retrieve it from apache_request_headers()
      *
      * @see https://github.com/symfony/symfony/issues/7170
      *
      * @param HeaderBag $headers
      */
     protected static function fixAuthHeader(HeaderBag $headers)
     {
         if (!$headers->has('Authorization') && function_exists('apache_request_headers')) {
             $all = apache_request_headers();
             if (isset($all['Authorization'])) {
                 $headers->set('Authorization', $all['Authorization']);
             }
         }
     }
 }
