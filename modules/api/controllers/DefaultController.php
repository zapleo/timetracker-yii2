<?php

namespace app\modules\api\controllers;

use app\models\TrackerVersion;
use app\models\User;
use app\models\WorkLog;
use Yii;
use yii\base\ErrorException;
use yii\filters\ContentNegotiator;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\Response;

/**
 * Default controller for the `api` module
 */
class DefaultController extends Controller
{
    const TIME_START = 8;
    const TIME_END = 18;

    public function behaviors()
    {
        Yii::$app->controller->enableCsrfValidation = false;

        $behaviors = parent::behaviors();
        $behaviors['contentNegotiator'] = [
            'class' => ContentNegotiator::className(),
            'formats' => [
                'application/json' => Response::FORMAT_JSON
            ]

        ];

        return $behaviors;
    }

    /**
     * @param $datetime
     * @return int
     */
    protected function checkWorkTime($datetime)
    {
        // workTime
        $work_time = 0;

        $date_time = round($datetime / 1000);
        // hour (int)
        $hour = date('H', $date_time);
        // day (int)
        $day = date('N', $date_time);

        if ($hour >= self::TIME_START && $hour < self::TIME_END)
            $work_time = 1;

        if ($day == 6 || $day == 7)
            $work_time = 0;

        return $work_time;
    }

    /**
     * @param $worklog
     * @return bool|int
     */
    protected function setLog($worklog)
    {
        $user = User::findByEmail($worklog['email']);

        if ($user) {
            $log = new WorkLog();

            $log->user_id = $user->id;
            $log->screenshot = $worklog['screenshot'];
            $log->dateTime = date('Y-m-d H:i:s', round($worklog['dateTime'] / 1000));
            $log->countMouseEvent = $worklog['countMouseEvent'];
            $log->countKeyboardEvent = $worklog['countKeyboardEvent'];
            $log->activityIndex = $worklog['activityIndex'];
            $log->issueKey = $worklog['issueKey'];
            $log->workTime = $this->checkWorkTime($worklog['dateTime']);

            if ($log->save())
                return $log->id;
        }

        return false;
    }

    /**
     * Renders the index view for the module
     * @return string
     */
    public function actionIndex()
    {
        return true;
    }

    /**
     * @return array|mixed|null|string|\yii\db\ActiveRecord
     * @throws BadRequestHttpException
     */
    public function actionLatestVersion()
    {
        try {
            $request = Yii::$app->request;

            if ($request->isGet) {
                return TrackerVersion::find()->select(['version', "DATE_FORMAT(`date`, '%Y-%m-%d') as date"])->orderBy(['tracker_version.date' => SORT_DESC])->one();
            }

            if ($request->isPost) {
                if ($request->post('newVersion')) {
                    $version = new TrackerVersion();
                    $version->version = $request->post('newVersion');
                    $version->save();

                    return $version->version;
                } else {
                    throw new BadRequestHttpException('Bad POST data!');
                }
            }

            throw new BadRequestHttpException('This request type not supported!');
        } catch (ErrorException $e) {
            throw new BadRequestHttpException('Global request error!');
        }
    }

    /**
     * @return bool|int
     * @throws BadRequestHttpException
     */
    public function actionLog()
    {
        try {
            $request = Yii::$app->request;

            if ($request->isPost) {
                $body = Yii::$app->getRequest()->getBodyParams();

                $hash = md5($body['workLog'][0]['dateTime']);

                if ($body['auth'] === $hash) {
                    $log_id = $this->setLog($body['workLog'][0]);

                    return $log_id;
                } else {
                    throw new BadRequestHttpException('Authorization check failed!');
                }
            } else {
                throw new BadRequestHttpException('This request type not supported!');
            }
        } catch (ErrorException $e) {
            throw new BadRequestHttpException('Global request error!');
        }
    }

    /**
     * @return bool
     * @throws BadRequestHttpException
     */
    public function actionLogs()
    {
        try {
            $request = Yii::$app->request;

            if ($request->isPost) {
                $body = Yii::$app->getRequest()->getBodyParams();

                $hash = md5($body['workLogs'][0]['dateTime']);

                if ($body['auth'] === $hash) {
                    foreach ($body['workLogs'] as $log) {
                        $this->setLog($log);
                    }

                    return true;
                } else {
                    throw new BadRequestHttpException('Authorization check failed!');
                }
            } else {
                throw new BadRequestHttpException('This request type not supported!');
            }
        } catch (ErrorException $e) {
            throw new BadRequestHttpException('Global request error!');
        }
    }
}