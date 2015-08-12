<?php
/**
 * Copyright 2015 Venditan Limited
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
namespace Venditan;

/**
 * Venditan Success PHP Client
 *
 * Used to set expectations of success.
 *
 * This class has been designed to have the minimum number of dependencies - e.g. no requirement for cURL or JSON
 *
 * @author Tom Walder <tom@venditan.com>
 */
class Success
{
    /**
     * Values that should never change!
     */
    const CANCEL = 'cancel';
    const TRIAL = 'trial';

    /**
     * Sent yet? We only want to do this once!
     *
     * @var bool
     */
    private $bol_sent = false;

    /**
     * What event are we monitoring?
     *
     * @var string
     */
    private $str_event = null;

    /**
     * What is the source of the event?
     *
     * @var string
     */
    private $str_source = null;

    /**
     * Who should we tell via email when something goes wrong?
     *
     * @var string
     */
    private $str_email = null;

    /**
     * Who should we tell via SMS when something goes wrong?
     *
     * @var string
     */
    private $str_sms = null;

    /**
     * Expected Interval
     *
     * @var string
     */
    private $str_every = null;

    /**
     * Optional message
     *
     * @var string
     */
    private $str_message = null;

    /**
     * Access token
     *
     * @var string
     */
    private $str_token = self::TRIAL;

    /**
     * Set up the event and hostname on construction.
     *
     * Objects should be crated by the expect() factory method
     *
     * @param $str_event
     */
    private function __construct($str_event)
    {
        $this->str_event = $str_event;
        $this->str_source = gethostname();
    }

    /**
     * This is the primary entry point
     *
     * @param String $str_event Event UID
     * @return Success
     */
    public static function expect($str_event)
    {
        return new self($str_event);
    }

    /**
     * What is the 'source' of the event?
     *
     * @param $str_from
     * @return $this
     */
    public function from($str_from)
    {
        $this->str_source = $str_from;
        return $this;
    }

    /**
     * This is a bad thing that should never have happened
     */
    public function never()
    {
        $this->str_every = 'never';
        return $this;
    }

    /**
     * Whatever is happening, should happen every INTERVAL
     *
     * Supported intervals are one of the following standard strings
     * - minute, hour, day, week, month
     *
     * OR, one of the following time representations, where N is a number
     * - Nm, Nh, Nd
     *
     * @param $str_interval
     * @return $this
     */
    public function every($str_interval)
    {
        $this->str_every = $str_interval;
        return $this;
    }

    /**
     * Cancel monitoring this event UID
     *
     * @return $this
     */
    public function cancel()
    {
        $this->str_every = self::CANCEL;
        return $this;
    }

    /**
     * Who should we try and tell via email?
     *
     * Provide either email address, recipient handle or group handle
     *
     * @param $str_recipient
     * @return $this
     */
    public function email($str_recipient)
    {
        $this->str_email = $str_recipient;
        return $this;
    }

    /**
     * Who should we try and tell via SMS?
     *
     * Provide either mobile number, recipient handle or group handle
     *
     * @param $str_recipient
     * @return $this
     */
    public function sms($str_recipient)
    {
        $this->str_sms = $str_recipient;
        return $this;
    }

    /**
     * Set your account access token
     *
     * @param $str_token
     * @return $this
     */
    public function token($str_token)
    {
        $this->str_token = $str_token;
        return $this;
    }

    /**
     * Optional message, used for logging and additional context information
     *
     * @param $str_message
     * @return $this
     */
    public function message($str_message)
    {
        $this->str_message = $str_message;
        return $this;
    }

    /**
     * If you are validating that a two-step process concludes correctly
     *
     * Supply any valid interval, for example: 1h
     *
     * @param $str_interval
     * @return Success
     */
    public function once($str_interval)
    {
        return $this->every('once:' . $str_interval);
    }

    /**
     * Send the data off to the server
     *
     * @todo Consider how to provide better SSL support with CA file location, ciphers etc
     *
     * @return bool
     */
    public function send()
    {
        $str_base_url = 'https://venditan-success.appspot.com/expect?';;
        if(function_exists('curl_init')) {
            $res_ch = curl_init();
            curl_setopt($res_ch, CURLOPT_ENCODING, "");
            curl_setopt($res_ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($res_ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($res_ch, CURLOPT_URL, $str_base_url . http_build_query($this->buildPayload(['tx' => 'curl'])));
            $str_response = curl_exec($res_ch);
            curl_close($res_ch);
        } else {
            $str_response = @file_get_contents($str_base_url . http_build_query($this->buildPayload(['tx' => 'fgc'])), false,
                stream_context_create([
                    'ssl' => [
                        'verify_peer' => true,
                        'CN_match' => '*.appspot.com',
                        'disable_compression' => true
                    ]
                ])
            );
            // $http_response_header
        }
        return $this->processResponse($str_response);
    }

    /**
     * Build and return the request payload
     *
     * @param null $arr_extras
     * @return array
     */
    private function buildPayload($arr_extras = null)
    {
        $arr_params = [
            'token' => $this->str_token,
            'source' => (null === $this->str_source ? gethostname() : $this->str_source),
            'event' => $this->str_event,
            'every' => $this->str_every,
            'email' => $this->str_email,
            'sms' => $this->str_sms,
            'message' => $this->str_message
        ];
        if(is_array($arr_extras)) {
            $arr_params = array_merge($arr_params, $arr_extras);
        }
        return $arr_params;
    }

    /**
     * Process response from the server
     *
     * @param $str_response
     * @return bool
     */
    private function processResponse($str_response)
    {
        if (false === $str_response) {
            $this->warning('Communication error, HTTP GET failed?');
        } else {
            $obj_response = json_decode($str_response);
            if(false === $obj_response) {
                $this->warning('Invalid JSON response from server: ' . $str_response);
            } else {
                if(isset($obj_response->success) && true === $obj_response->success) {
                    return true;
                }
                if(isset($obj_response->messages)) {
                    $this->warning('Failed. Messages from server: ' . implode(', ', $obj_response->messages));
                } else {
                    $this->warning('Unexpected response from server: ' . $str_response);
                }
            }
        }
        return false;
    }

    /**
     * Warning message
     *
     * @param $str_message
     */
    private function warning($str_message)
    {
        trigger_error('[Venditan/Success] ' . $str_message, E_USER_WARNING);
    }

    /**
     * Automatically send on shutdown, if not already sent
     */
    public function __destruct()
    {
        if (!$this->bol_sent) {
            $this->send();
        }
    }
}