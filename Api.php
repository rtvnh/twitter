<?php
/**
 * Ctwitter.php
 *
 * Curl version of Twitter api 1.1 search
 *
 * @package Rtvnh
 * @subpackage Twitter
 * @version 1.0
 * @author WillemDaems
 */
namespace Rtvnh\Twitter;

use Rtvnh\Twitter\Auth\OAuth as Auth;

class Api
{

    /**
     * Set content-type for general output
     * @var string
     */
    public static $contentType = 'application/json';

    /**
     * Sitebased configuration
     * @var Zend_Config_Xml
     */
    private $auth;

    /**
     * Limit for search results
     * @var int
     */
    private $count = 40;

    /**
     * Twitter api url
     * @var string
     */
    private $url = 'https://api.twitter.com/1.1/search/tweets.json';

    /**
     * Class constructor
     *
     * @param Rtvfeeds_Library_Feeds_Feed $feed
     * @todo Find good way to implement params
     * @return void
     */
    public function __construct($consumerKey, $accessToken, $consumerSecret, $accessTokenSecret)
    {
        // twitter params, because of strange behavior in twitter api they are set separate
        $this->params = array();

        // the passed query param
        $this->query = rawurlencode($_GET['search'] );
        
        // assign OAuth class
        $this->auth = new Auth($consumerKey, $accessToken, $consumerSecret, $accessTokenSecret);
    }

    /**
     * Create oAuth 1.0 signature and do a cUrl
     * Notice the strange order of parameters
     *
     * @return array
     */
    public function search($query, $max = 20)
    {
        $curl_request = curl_init();

        if (!empty($this->params)) {
            foreach ($this->params as $key => $value) {
                if (empty($str)) {
                    $str = '?' . $key.'='.$value;
                } else {
                    $str .= '&' . $key.'='.$value;
                }
            }
        }

        curl_setopt($curl_request, CURLOPT_HTTPHEADER, $this->auth->oauthHeader());
        curl_setopt($curl_request, CURLOPT_HEADER, false);
        curl_setopt($curl_request, CURLOPT_URL, $this->url . '?count='.$max.'&q='.$query);
        curl_setopt($curl_request, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl_request, CURLOPT_SSL_VERIFYPEER, false);

        $json = curl_exec($curl_request);

        curl_close($curl_request);

        $tweets = json_decode($json);

        // json encoded tweets
        $tweets = $this->getTweets($tweets);

        return $tweets;
    }

    /**
     * Parse the result passed through this function
     * @param array objects $tweets
     * @return array
     */
    private function getTweets($tweets)
    {
        // holder for tweets
        $storage = array();

        if (empty($tweets->errors)) {
            // loop all the things!
            foreach ($tweets->statuses as $tweet) {

                // empty urls array
                $urls = array();

                // empty hashtags array
                $hashtags = array();

                // editable version of the tweet
                $text = $tweet->text;

                // get and parse possible url's
                if (!empty($tweet->entities->urls)) {
                    foreach ($tweet->entities->urls as $urlArray) {
                        $urls[] = array(
                            'url' => $urlArray->url,
                            'expanded_url' => $urlArray->expanded_url,
                            'display_url' => $urlArray->display_url
                        );

                        $text = str_replace(
                            $urlArray->url,
                            '<a href="'.$urlArray->expanded_url.'" target="_blank">'.$urlArray->display_url.'</a>',
                            $text
                        );
                    }
                }

                // get and parse hashtags
                if (!empty($tweet->entities->hashtags)) {
                    foreach ($tweet->entities->hashtags as $hashtag) {
                        $hashtags[] = array(
                            'text' => $hashtag->text
                        );

                        $text = str_replace(
                            '#'.$hashtag->text,
                            '<a href="http://www.twitter.com/#'.$hashtag->text.'" target="_blank">#'.$hashtag->text.'</a>',
                            $text
                        );
                    }
                }

                if (!empty($tweet->entities->user_mentions)) {
                    foreach ($tweet->entities->user_mentions as $mention) {

                        $text = str_replace(
                            '@'.$mention->screen_name,
                            '<a href="http://www.twitter.com/@'.$mention->screen_name.'" target="_blank">@'.$mention->screen_name.'</a>',
                            $text
                        );
                    }
                }

                // empty user array


                $user = array(
                        'screen_name' => $tweet->user->screen_name,
                        'picture' => $tweet->user->profile_image_url
                    );


                // shortened tweet array
                $storage[] = array(
                    'rawText' => $tweet->text,
                    'text' => $text,
                    'timestamp' => strtotime($tweet->created_at),
                    'id' => $tweet->id,
                    'userid' => $tweet->user->id,
                    'urls' => $urls,
                    'hashtags' => $hashtags,
                    'user' => $user
                );
            }
        }

        return json_encode($storage);
    }

    /**
     * @deprecated Not necessary anymore to fetch screenname from this
     * @param int $idUser
     * @param string $screenName
     * @return array
     */
    private function getTwitterUser($idUser, $screenName = '')
    {
        $connection = Rtvnh_Library_Twitter_Connection::getInstance()->getConnection();

        // set parameters
        $parameters = array(
            'user_id'=> $idUser
        );

        // fetch tweets
        $user = $connection->get('users/show', $parameters);

        return $user;
    }
}
