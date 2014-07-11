<?php

namespace Rtvnh\Twitter\Auth;

class OAuth
{

    private $consumerKey;

    private $accessToken;

    private $consumerSecret;

    private $accessTokenSecret;

    private $query;

    private $url;

    public function __construct($consumerKey, $accessToken, $consumerSecret, $accessTokenSecret)
    {
        $this->consumerKey = $consumerKey;
        $this->accessToken = $accessToken;
        $this->consumerSecret = $consumerSecret;
        $this->accessTokenSecret = $accessTokenSecret;
    }

    public function setQuery($query)
    {
        $this->query = $query;
    }

    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * OAuth signature generation for header
     * @return string
     */
    private function oauthSignature()
    {
        $oauth_hash = '';

        // consumer key
        $oauth_hash .= 'count=' . $this->count;

        $oauth_hash .= '&oauth_consumer_key=' . $this->consumerKey;

        $oauth_hash .= '&oauth_nonce=' . time() . '&';

        $oauth_hash .= 'oauth_signature_method=HMAC-SHA1&';

        $oauth_hash .= 'oauth_timestamp=' . time() . '&';

        $oauth_hash .= 'oauth_token='.$this->accessToken;

        $oauth_hash .= '&oauth_version=1.0';

        $oauth_hash .= '&q='.$this->query;

        $base = 'GET';

        $base .= '&';

        $base .= rawurlencode($this->url);

        $base .= '&';

        $base .= rawurlencode($oauth_hash);

        // consumer secret
        $key = rawurlencode($this->consumerSecret);

        $key .= '&';

        $key .= rawurlencode($this->accessTokenSecret);

        $signature = base64_encode(hash_hmac('sha1', $base, $key, true));

        $signature = rawurlencode($signature);

        return $signature;
    }

    /**
     * Create the header for sending in the cUrl
     * @return array
     */
    public function oauthHeader()
    {
        $oauth_header = 'count="'.$this->count.'", ';

        $oauth_header .= 'oauth_consumer_key="'.$this->consumerKey.'", ';

        $oauth_header .= 'oauth_nonce="' . time() . '", ';

        $oauth_header .= 'oauth_signature="' . $this->oauthSignature() . '", ';

        $oauth_header .= 'oauth_signature_method="HMAC-SHA1", ';

        $oauth_header .= 'oauth_timestamp="' . time() . '", ';

        $oauth_header .= 'oauth_token="'.$this->accessToken.'", ';

        $oauth_header .= 'oauth_version="1.0", ';

        $oauth_header .= 'q="'.$this->query.'", ';

        return array("Authorization: Oauth {$oauth_header}", 'Expect:');
    }
}