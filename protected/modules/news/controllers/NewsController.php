<?php

/**
 * NewsController контроллер для работы с новостями в публичной части сайта
 *
 * @author yupe team <team@yupe.ru>
 * @link http://yupe.ru
 * @copyright 2009-2013 amyLabs && Yupe! team
 * @package yupe.modules.news.controllers
 * @since 0.1
 *
 */
class NewsController extends \yupe\components\controllers\FrontController
{
    public function actionShow($alias)
    {
        $model = News::model()->published();

        $model = ($this->isMultilang())
            ? $model->language(Yii::app()->language)->find('alias = :alias', [':alias' => $alias])
            : $model->find('alias = :alias', [':alias' => $alias]);

        if (!$model) {
            throw new CHttpException(404, Yii::t('NewsModule.news', 'News article was not found!'));
        }

        // проверим что пользователь может просматривать эту новость
        if ($model->is_protected == News::PROTECTED_YES && !Yii::app()->user->isAuthenticated()) {
            Yii::app()->user->setFlash(
                yupe\widgets\YFlashMessages::ERROR_MESSAGE,
                Yii::t('NewsModule.news', 'You must be an authorized user for view this page!')
            );

            $this->redirect([Yii::app()->getModule('user')->accountActivationSuccess]);
        }

        $this->render('show', ['model' => $model]);
    }

    public function actionIndex()
    {
        $dbCriteria = new CDbCriteria([
            'condition' => 't.status = :status',
            'params'    => [
                ':status' => News::STATUS_PUBLISHED,
            ],
            'order'     => 't.creation_date DESC',
            'with'      => ['user'],
        ]);

        if (!Yii::app()->user->isAuthenticated()) {
            $dbCriteria->mergeWith(
                [
                    'condition' => 'is_protected = :is_protected',
                    'params'    => [
                        ':is_protected' => News::PROTECTED_NO
                    ]
                ]
            );
        }

        if ($this->isMultilang()) {
            $dbCriteria->mergeWith(
                [
                    'condition' => 't.lang = :lang',
                    'params'    => [':lang' => Yii::app()->language],
                ]
            );
        }

        $dataProvider = new CActiveDataProvider('News', [
            'criteria' => $dbCriteria,
            'pagination' => [
                'pageSize' => $this->module->perPage
            ]
        ]);
        $this->render('index', ['dataProvider' => $dataProvider]);
    }
}
