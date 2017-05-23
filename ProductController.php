<?php

namespace frontend\controllers;

use Yii;
use common\models\essence\Ads;
use common\models\mm\MmWish;
use common\models\mm\MmCollection;
use frontend\models\searchmodels\LotSerch;
use frontend\models\searchmodels\ProductSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use common\models\essence\Cat;
use yii\widgets\ActiveForm;
use common\models\sp\SpBrands;
use common\models\essence\Properties;
use common\models\essence\Banner;

use common\models\essence\Product;

/**
 * LotController implements the CRUD actions for Ads model.
 */
class ProductController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['addwish'],
                'rules' => [
                    [
                        'actions' => ['addwish', 'addcollection', 'view', 'index', 'search', 'users'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],

            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }


    /**
     * @return mixed
     */
    public function actionSearch()
    {
        $q = Yii::$app->request->get('q');
        $cats = [];
        $ids = [];
        $searchModel = new ProductSearch();
        $params = Yii::$app->request->post();
        $params['ProductSearch']['searchtext'] = urldecode($q);
        $dataProvider = $searchModel->search($params);
        $view = (isset($params['view_filter']) && $params['view_filter'] == 'list') ? 'list' : 'row';

        foreach ($dataProvider->getModels() as $model) {
            $cats[] = $model->cat_id;
            $ids[] = $model->id;
        }

        $ids = array_unique($ids);
        $cats = array_unique($cats);

        //Filter
        $filter['brands'] = SpBrands::getFilterData($ids);

        $filter['properties'] = Properties::getFilterData($ids);

        return $this->render('search', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'view' => $view,
            'cats' => $cats,
            'filter' => $filter,
        ]);
    }


    /**
     * Lists all Ads models.
     * @param $cat
     * @return mixed
     */
    public function actionIndex($cat)
    {
        if (!$cat = Cat::find()->where(['urlname' => $cat])->one()) {
            throw new NotFoundHttpException('Категория не найдена');
        }

        \Yii::$app->params['category_name'] = $cat->getFirstParentCat()->urlname;

        $searchModel = new ProductSearch();

        $params = Yii::$app->request->post() ? Yii::$app->request->post() : Yii::$app->request->get();

        $params['ProductSearch']['cat_id'] = $cat->id;

        $dataProvider = $searchModel->search($params);
        $view = (isset($params['view_filter']) && $params['view_filter'] == 'list') ? 'list' : 'row';

        //Filter
        $filter['brands'] = SpBrands::getFilterData($ids);

        $filter['properties'] = Properties::getFilterData($ids);


        $banners = Banner::find()->where(['cat_id' => $cat->id])
            ->orderBy(['sort' => SORT_ASC])
            ->all();

        return $this->render('index', [
            'cat' => $cat,
            'view' => $view,
            'params' => !empty($params['ProductSearch']) ? $params['ProductSearch'] : [],
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'filter' => $filter,
            'start' => $start,
            'banners' => $banners,
        ]);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function actionUsers($id)
    {
        $model = $this->findModel($id);

        return $this->render('users', [
            'model' => $model,
            'action' => 'user'
        ]);
    }

    /**
     * Finds the Ads model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Ads the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Product::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    /**
     * @param $cat
     * @param $id
     * @return mixed
     */
    public function actionView($cat, $id)
    {

        if (!$cat = Cat::find()->where(['urlname' => $cat])->one()) {
            throw new NotFoundHttpException('Категория не найдена');
        }

        \Yii::$app->params['category_name'] = $cat->getFirstParentCat()->urlname;

        return $this->render('view', [
            'p' => $this->findModel($id),
        ]);
    }

    /**
     * @param $id
     * @return bool|string
     */
    public function actionAddwish($id)
    {
        $model = MmWish::find()->where(['user_id' => Yii::$app->user->id, 'product_id' => $id])->one();

        if ($model) {
            $model->delete();
            return 'success';
        }

        $model = new MmWish;
        $model->product_id = $id;
        $model->user_id = Yii::$app->user->id;

        if ($model->save()) {
            return 'success';
        }
        return false;
    }


    /**
     * @param $id
     * @return bool|string
     */
    public function actionAddcollection($id)
    {
        $model = MmCollection::find()->where(['user_id' => Yii::$app->user->id, 'product_id' => $id])->one();

        if ($model) {
            $model->delete();
            return json_encode(['status' => 'success']);
        }

        $model = new MmCollection;
        $model->product_id = $id;
        $model->user_id = Yii::$app->user->id;

        if ($model->save()) {
            return json_encode(['status' => 'success']);
        }
        return json_encode(['status' => 'fail']);
    }
}
