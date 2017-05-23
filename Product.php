<?php

namespace common\models\essence;

use Yii;
use common\models\mm\MmCollection;
use common\models\mm\MmWish;
use common\models\User;

/**
 * This is the model class for table "product".
 *
 * @property integer $id
 * @property integer $cat_id
 * @property integer $brands_watch
 * @property integer $brands_jewelry
 * @property integer $product_status_id
 * @property integer $user_id
 * @property string $insert
 * @property string $name
 * @property string $description
 *
 * @property Ads[] $ads
 * @property Cat $cat
 * @property SpBrandsJewelry $brandsJewelry
 * @property SpBrandsWatch $brandsWatch
 * @property SpStatusProduct $productStatus
 * @property User $user
 * @property ProductPhoto[] $productPhotos
 */
class Product extends \yii\db\ActiveRecord
{

    const CATEGORY_WATCH = 1;
    const CATEGORY_JEWELRY = 2;
    public $parent_cat_id;
    protected $product_name;
    protected $arr_name = [];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'product';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['cat_id', 'product_status_id', 'user_id', 'brand_id'], 'required', 'message' => 'Заполните поле "{attribute}"'],
            [['parent_cat_id', 'cat_id', 'brand_id', 'product_status_id', 'user_id', 'product_model'], 'integer'],
            [['brand_id'], 'isCorrect'],
            [['product_model_ref'], 'string', 'max' => 255],
            [['created_at'], 'safe'],
            [['name'], 'string', 'max' => 100],
            [['description', 'tags'], 'string', 'max' => 1000]
        ];
    }

    /**
     * @param $attribute
     */
    public function isCorrect($attribute)
    {
        if ($this->model) {
            if ($this->brand_id != $this->model->getFirstParentModel()->brand_id) {
                $this->addError($attribute, 'Модель должна принадлежать бренду. Текущий бренд модели: ' . $this->model->getFirstParentModel()->brand->name);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'parent_cat_id' => 'Категория',
            'cat_id' => 'Подкатегория',
            'brand_id' => 'Бренд',
            'product_status_id' => 'Статус',
            'user_id' => 'Пользователь, который внёс',
            'created_at' => 'Добавлено',
            'name' => 'Наименование',
            'description' => 'Описание',
            'product_model_ref' => 'Модель/REF',
            'product_model' => 'Модель',
            'tags' => 'Теги для поиска',
        ];
    }

    /**
     * @param $insert
     * @return bool
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            $tags = $this->cat->name . ' ' .
                $this->getName();
            $this->tags = $tags;

            if (!$this->name) {
                $this->name = $this->getName();
            }

            return true;
        }
        return false;
    }

    /**
     * Name construction
     *
     * @return string
     */
    public function getName()
    {
        if ($this->brand) {
            $this->product_name = $this->brand->name . ' ';
        }
        if ($this->model) {
            $this->arr_name[] = $this->model->name;
        }

        $this->product_name .= $this->modelsName($this->model);

        $this->product_name .= ' ' . $this->name;

        if ($this->product_model_ref) {
            $this->product_name .= ' (' . $this->product_model_ref . ')';
        }

        return $this->product_name;
    }

    /**
     * @param $model
     * @return string
     */
    private function modelsName($model)
    {

        if (!empty($model->parent)) {
            $this->arr_name[] = $model->parent->name;
            $this->modelsName($model->parent);
        }

        return implode(' ', array_reverse($this->arr_name));
    }

    /**
     * Ads Count Text
     *
     * @return string
     */
    public function getAdsCountText()
    {
        $number = count($this->ads);
        $endingArray = ['объявление', 'объявления', 'объявлений'];
        $number = $number % 100;

        if ($number >= 11 && $number <= 19) {
            $ending = $endingArray[2];
        } else {
            $i = $number % 10;
            switch ($i) {
                case (1):
                    $ending = $endingArray[0];
                    break;
                case (2):
                case (3):
                case (4):
                    $ending = $endingArray[1];
                    break;
                default:
                    $ending = $endingArray[2];
            }
        }

        return '<span>(' . $number . ' ' . $ending . ')</span>';
    }

    /**
     * Props List
     *
     * @return mixed
     */
    public function getPropList()
    {
        $model = Properties::find()->joinWith('propCats')
            ->andWhere(['IN', 'mm_cat_prop.cat_id', [$this->cat_id, $this->cat->parent_id]])
            ->andWhere(['NOT IN', 'prop_id', ProductProp::find()
                ->select(['prop_id'])
                ->where(['product_id' => $this->id])
                ->asArray()->all()
            ])
            ->all();
        return $model;
    }

    /**
     * Get First Image
     *
     * @return string
     */
    public function getFirstImage()
    {
        if ($this->productPhotos) {
            return $this->productPhotos[0]->photo;
        }

        return '/img/nopic.png';
    }

    /**
     * Set Properties
     *
     * @param $prop
     * @param $val
     * @return bool
     */
    public function setProp($prop, $val)
    {
        $property = Properties::find()->where(['prop_name' => $prop])->one();
        $model = ProductProp::find()->where(['product_id' => $this->id, 'prop_id' => $property->id])->one();

        if ($model) {
            $model->prop_value = $val;
            if ($model->save()) return true;
        } else {
            $model = new ProductProp;
            $model->product_id = $this->id;
            $model->prop_id = $property->id;
            $model->prop_value = $val;
            if ($model->save()) return true;
        }

        return false;
    }

    /**
     * Get Properties
     *
     * @param $prop
     * @return bool
     */
    public function getProp($prop)
    {
        $model = ProductProp::find()->joinWith('props')->where(['product_id' => $this->id, 'properties.prop_name' => $prop])->one();

        if ($model) {
            return $model->prop_value;
        }

        return false;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAds()
    {
        return $this->hasMany(Ads::className(), ['product_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getActiveAds()
    {
        return $this->hasMany(Ads::className(), ['product_id' => 'id'])->where(['status_id' => 4]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProps()
    {
        return $this->hasMany(ProductProp::className(), ['product_id' => 'id']);
    }

    /**
     * Min price
     *
     * @return integer
     */
    public function getMinPrice()
    {
        $queryValue = Ads::find()->where(['product_id' => $this->id])->andWhere(['>=', 'status_id', 4])->min('price');
        return $queryValue;
    }

    /**
     * Max price
     *
     * @return integer
     */
    public function getMaxPrice()
    {
        $queryValue = Ads::find()->where(['product_id' => $this->id])->andWhere(['>=', 'status_id', 4])->max('price');
        return $queryValue;
    }

    /**
     * Is in wishes
     *
     * @return object
     */
    public function getWish()
    {
        $queryValue = \common\models\mm\MmWish::find()->where(['product_id' => $this->id, 'user_id' => Yii::$app->user->id])->one();
        return $queryValue;
    }

    /**
     * Is in collections
     *
     * @return object
     */
    public function getCollection()
    {
        $queryValue = \common\models\mm\MmCollection::find()->where(['product_id' => $this->id, 'user_id' => Yii::$app->user->id])->one();
        return $queryValue;
    }

    /**
     * Get Url
     *
     * @return string
     */
    public function getUrl()
    {
        $cat = $this->cat->urlname ? $this->cat->urlname : $this->cat->parentcat->urlname;
        return '/' . $cat . '/' . $this->id;
    }


    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCat()
    {
        return $this->hasOne(Cat::className(), ['id' => 'cat_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getModel()
    {
        return $this->hasOne(Models::className(), ['id' => 'product_model']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBrand()
    {
        return $this->hasOne(\common\models\sp\SpBrands::className(), ['id' => 'brand_id']);
    }


    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProductStatus()
    {
        return $this->hasOne(SpStatusProduct::className(), ['id' => 'product_status_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProductPhotos()
    {
        return $this->hasMany(ProductPhoto::className(), ['product_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMmCollections()
    {
        return $this->hasMany(MmCollection::className(), ['product_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMmWishs()
    {
        return $this->hasMany(MmWish::className(), ['product_id' => 'id']);
    }
}
