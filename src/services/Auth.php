<?php

namespace Kodal\Email2FA\Services;

use Craft;
use Kodal\Email2FA\Plugin;
use putyourlightson\logtofile\LogToFile;

/**
 * Class Auth
 * @package Kodal\Email2FA\Services
 */
class Auth
{
    // Protected Properties
    // =========================================================================

    /**
     * @var bool|\craft\base\Model|null
     */
    protected $settings;

    /**
     * Auth constructor.
     * @throws \craft\errors\MissingComponentException
     */
    public function __construct()
    {

        $this->settings = Plugin::$plugin->getSettings();
        $this->response = Craft::$app->getResponse();
    }

    // Public Methods
    // =========================================================================

    /**
     * @throws \craft\errors\MissingComponentException
     */
    public function requireLogin()
    {
        if ( ! $this->isLoggedIn()) {
            $this->response->redirect($this->settings->verifyRoute);
        }
    }

    /**
     * @return bool
     * @throws \craft\errors\MissingComponentException
     */
    public function isLoggedIn()
    {
        $auth = Plugin::$plugin->cookie->get('auth');
        $hash = Plugin::$plugin->storage->get('hash');

        // LogToFile::info('Auth - isLoggedIn() - auth: '.print_r($auth, true), 'Email2FA');
        // LogToFile::info('Auth - isLoggedIn() - hash: '.print_r($hash, true), 'Email2FA');

        return is_string($auth) && is_string($hash) && !empty($auth) && !empty($hash) && $auth === $hash;
    }

    /**
     * @param $event
     *
     * @return \craft\web\Response|\yii\console\Response
     * @throws \Exception
     */
    public function login($event)
    {
        if (!$this->isLoggedIn()) {
            // LogToFile::info('Auth - not isLoggedIn', 'Email2FA');
            $this->triggerAuthChallenge();
        } else {
            // LogToFile::info('Auth - isLoggedIn', 'Email2FA');
        }

        // if (!$this->simulateVerification()) {
        //     LogToFile::info('not simulateVerification', 'Email2FA');
        //     $this->triggerAuthChallenge();
        // }
    }

    /**
     * @param $event
     */
    public function logout($event)
    {

    }

    /**
     * @param $hash string
     */
    public function twoFactorLogin(string $hash)
    {
        return Plugin::$plugin->cookie->set('auth', $hash, [
            'expire' => time() + $this->settings->verifyDuration
        ]);
    }

    // Protected Methods
    // =========================================================================

    /**
     * @return \craft\web\Response|\yii\console\Response
     */
    protected function triggerAuthChallenge()
    {
        Plugin::$plugin->verify->triggerAuthChallenge();

        // return $this->response->redirect($this->settings->verifyRoute);
    }

    /**
     * @return mixed
     */
    protected function simulateVerification()
    {
        Plugin::$plugin->verify->triggerAuthChallenge(false);

        $verifyCode = Plugin::$plugin->storage->get('verify');

        return Plugin::$plugin->verify->verify($verifyCode);
    }
}