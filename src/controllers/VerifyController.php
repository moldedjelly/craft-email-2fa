<?php

namespace Kodal\Email2FA\controllers;

use Craft;
use craft\web\Controller;
use Kodal\Email2FA\Plugin;
use craft\helpers\App;

class VerifyController extends Controller
{
    /**
     * @var
     */
    private $session;

    /**
     * @var \craft\web\Response|\yii\console\Response
     */
    public $response;

    /**
     * @var bool|\craft\base\Model|null
     */
    private $settings;

    protected $allowAnonymous = ['index', 'hash'];

    /**
     * VerifyController constructor.
     *
     * @param       $id
     * @param       $module
     * @param array $config
     *
     * @throws \craft\errors\MissingComponentException
     */
    public function __construct($id, $module, $config = [])
    {
        $this->settings = Plugin::$plugin->getSettings();
        $this->session  = Craft::$app->getSession();
        $this->response = Craft::$app->response;

        parent::__construct($id, $module, $config);
    }

    /**
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionIndex()
    {
        $this->requirePostRequest();

        $request    = Craft::$app->getRequest();
        $verifyCode = str_split($request->getBodyParam('verifyCode'));

        $verified = $this->verify($verifyCode);

        // added by Matj
        $config = Craft::$app->getConfig()->getGeneral();
        $siteUrl = $config->aliases['@primarySiteUrl'];

        if ($verified) {
            $user = Craft::$app->getUser();
            // $this->session->setNotice(Craft::t('email-2fa', 'Logged in.'));

            // if(Craft::$app->getSession()->get('firstTimeLogin')) {
            //     return $this->redirect($config->general->activateAccountSuccessPath);
            // }

            // return $this->redirect($user->getReturnUrl());

            // added by Matj
            if ($request->getAcceptsJson()) {
                return $this->asJson(['success' => true]);
            } else {
                return $this->redirect($siteUrl.'account/auth-verify');
            }

        } else {
            // $this->session->setError(Craft::t('email-2fa', 'Verification failed.'));

            // return $this->redirect($this->settings->verifyRoute);

            // added by Matj
            if ($request->getAcceptsJson()) {
                return $this->asJson(['error' => 'That code is invalid']);
            } else {
                return $this->redirect($siteUrl.'account/auth-verify?failed=1');
            }
        }
    }

    /**
     *
     */
    public function actionHash()
    {
        $request = Craft::$app->getRequest();
        $hash    = $request->getQueryParam('v');

        $hashFromSession = Plugin::$plugin->storage->get('hash');

        if ($hash !== $hashFromSession) {
            $this->session->setError(Craft::t('email-2fa', 'Automatic verification failed.'));

            return $this->redirect($this->settings->verifyRoute);
        }

        $verifyCode = Plugin::$plugin->storage->get('verify');
        $verified   = $this->verify($verifyCode);

        // added by Matj
        $config = Craft::$app->getConfig()->getGeneral();
        $siteUrl = $config->aliases['@primarySiteUrl'];

        if ($verified) {
            $user = Craft::$app->getUser();
            // $this->session->setNotice(Craft::t('email-2fa', 'Logged in.'));

            // if(Craft::$app->getSession()->get('firstTimeLogin')) {
            //     return $this->redirect($config->general->activateAccountSuccessPath);
            // }

            // return $this->redirect($user->getReturnUrl());

            // added by Matj
            return $this->redirect($siteUrl.'account/auth-verify');

        } else {
            // $this->session->setError(Craft::t('email-2fa', 'Verification failed.'));

            // return $this->redirect($this->settings->verifyRoute);

            // added by Matj
            return $this->redirect($siteUrl.'account/auth-verify?failed=1');
        }
    }

    /**
     *
     */
    public function actionResend()
    {
        $verifyCode = Plugin::$plugin->storage->get('verify');
        $hash       = Plugin::$plugin->storage->get('hash');

        Plugin::$plugin->verify->resendVerifyEmail($verifyCode, $hash);

        return $this->redirect($this->settings->verifyRoute);
    }

    /**
     * @param $verifyCode
     */
    protected function verify($verifyCode)
    {
        try {
            return Plugin::$plugin->verify->verify($verifyCode);
        } catch (\Exception $e) {
            $this->session->setError($e->getMessage());

            return false;
        }
    }
}