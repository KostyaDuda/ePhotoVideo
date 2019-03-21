<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\SetingsForm;
use app\models\ImageUpload;
use app\models\ContactForm;
use app\models\Raiting;
use app\models\User_fv;
use app\models\Vacancy;
use app\models\User_content;
use yii\data\Pagination;
use yii\web\UploadedFile;

class SiteController extends Controller
{
     
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['logout'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * Login action.
     *
     * @return Response|string
     */


    /**
     * Displays about page.
     *
     * @return string
     */
    // public function actionAbout()
    // {
    //     return $this->render('about');
    // }

    public function actionView($id)
    {
        $user_one = User_fv::findOne($id);
       $contents = User_content::find()->where(['user_id'=> $id])->all();
       $vacancies = Vacancy::find()->where(['id_user'=> $id])->all();
        return $this->render('view',
        [
            'user_one'=>$user_one,
            'contents' => $contents,
            'vacancies' => $vacancies
        ]
    );
    }
    public function actionSettings($id)
    {
        if($id != Yii::$app->user->identity->id)
            throw new \yii\web\ForbiddenHttpException("У вас немає прав для редагування даного користувача");

        $user_one = User_fv::findOne($id);
        if(!$user_one)
            throw new \yii\web\NotFoundHttpException("Користувач не знайдений");

        if(Yii::$app->request->isPost)
        {
            $user_one->load(Yii::$app->request->post());
            if($user_one->setings_update())
            {
                return $this->redirect(['site/view/','id'=>$id]);
            }
        }
        return $this->render('settings',
        [
            'user_one'=>$user_one
        ]
    );
    }

    public function actionSetImage($id)
    {
        $model = new ImageUpload;
        if (Yii::$app->request->isPost)
        {
            $user = User_fv::findOne($id);
            $file = UploadedFile::getInstance($model, 'image');

            if($user->saveImage($model->uploadFile($file, $user->img)))
            {
                return $this->redirect(['site/settings', 'id'=>$user->id]);
            }
        }
        
        return $this->render('upload_avatar', ['model'=>$model]);
    }

    public function actionSetContent($id)
    {
        $model = new ImageUpload;
        if (Yii::$app->request->isPost)
        {
            $user = new User_content;
            $file = UploadedFile::getInstance($model, 'image');

            if($user->saveImage_content($model->uploadFile($file),"фото",$id))
            {
                return $this->redirect(['site/view', 'id'=>$id]);
            }
        }
        
        return $this->render('upload_avatar', ['model'=>$model]);
    }

    public function actionSetVideo()
    {     
        $model = new User_content();
        if(Yii::$app->request->isPost)
        {
            $model->load(Yii::$app->request->post());
            if($model->saveVideo(Yii::$app->user->id))
            {
                return $this->redirect(['view','id'=>Yii::$app->user->id]);
            }
        }
        return $this->render('insert_video', ['model'=>$model]);
    }

    public function actionSetVacancy()
    {     
        $model = new Vacancy();
        if(Yii::$app->request->isPost)
        {
            $model->load(Yii::$app->request->post());
            if($model->saveVacancy(Yii::$app->user->id))
            {
                return $this->redirect(['view','id'=>Yii::$app->user->id]);
            }
        }
        return $this->render('insert_vacancy', ['model'=>$model]);
    }


    public function actionRaiting()
    {
        $query = Raiting::find();//->Where(['>', 'status', 0]);
        
        $count = $query->count();
        $pagination = new Pagination(['totalCount' => $count,'pageSize'=>2]);
        $users = $query->offset($pagination->offset)->limit($pagination->limit)->all();     
        return $this->render('raiting',[
            'users'=>$users,
            'pagination'=>$pagination
            ]);
    }

    public function actionVacancy()
    {
        $query = Vacancy::find();//->Where(['>', 'status', 0]);
        $user = User_fv::find()->all();
        $count = $query->count();
        $pagination = new Pagination(['totalCount' => $count,'pageSize'=>1]);
        $vacancies = $query->offset($pagination->offset)->limit($pagination->limit)->all();     
        return $this->render('list_vacancy',[
            'vacancies'=>$vacancies,
            'pagination'=>$pagination,
            'user' => $user
            ]);
    }

    public function actionDeleteContent($id)
    {
        $content = User_content::find()->where(['id'=>$id])->one();
        $content->delete();

        return $this->redirect(['view', 'id'=>Yii::$app->user->id]);
    }
    public function actionDeleteVacancy($id)
    {
        $content = Vacancy::find()->where(['id'=>$id])->one();
        $content->delete();

        return $this->redirect(['view', 'id'=>Yii::$app->user->id]);
    }


}
