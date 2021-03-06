<?php
/*
 * @Author: dingbing 
 * @Date: 2017-11-23 20:37:14 
 * @Last Modified by: dingbing
 * @Last Modified time: 2017-11-23 20:38:05
 */
namespace common\models;

use common\helpers\Tools;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;


/**
 * This is the model class for table "{{%category}}".
 *
 * @property string $cat_id
 * @property string $cat_name
 * @property integer $sort
 * @property integer $is_show
 * @property string $parent_id
 *
 * @property Goods[] $goods
 */
class Category extends \yii\db\ActiveRecord
{

    const IS_SHOW = 1;      // 展示
    const BASE_CATE = 0;    // 根分类

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%category}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['sort', 'is_show', 'parent_id'], 'integer'],
            [['cat_name'], 'unique'],
            [['cat_name'], 'string', 'max' => 45],
            ['parent_id','default','value'=>0]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'cat_id' => 'Cat ID',
            'cat_name' => '栏目名称',
            'sort' => '排序',
            'is_show' => '前台是否显示',
            'parent_id' => '父级栏目',
        ];
    }

    /**
     * 加载默认值
     *
     * @param boolean $skipIfSet
     * @return void
     */
    public function loadDefaultValues($skipIfSet = true)
    {
        $this->is_show = 1;
        $this->sort = 50;
        return $this;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGoods()
    {
        return $this->hasMany(Goods::className(), ['cat_id' => 'cat_id']);
    }

    /**
     * 分类下拉菜单数据
     *
     * @param array $categories
     * @return array
     */
    public function dropDownList($categories=[])
    {
        if(empty($categories))
        {
            $categories = self::getLevelCategories(self::find()->asArray()->all());
        }

        $result = [];
        if(is_array($categories))
        {
            foreach ($categories as $value)
            {
                $result[$value['cat_id']] = str_repeat('|----',$value['level']).$value['cat_name'];
            }
        }
        return $result;
    }

    /**
     * 处理无限极分类
     *
     * @param array $categories
     * @param int $except           待排除的分类以及子分类
     * @param int $parentId
     * @param int $level
     * @return array
     */
    static public function getLevelCategories($categories=[],$except='',$parentId=0,$level=0)
    {
        static $result = [];
        if(is_array($categories))
        {
            foreach ($categories as $key=>$value)
            {
                if($value['parent_id'] == $parentId && $value['cat_id'] != $except)
                {
                    $value['level'] = $level;
                    $result[] = $value;
                    self::getLevelCategories($categories,$except,$value['cat_id'],$level+1);
                }
            }
        }

        return $result;
    }


    /**
     * 查询全部商品分类
     *
     * @param int $pid
     * @return array|\yii\db\ActiveRecord[]
     */
    static function getNavigation($pid=self::BASE_CATE)
    {
        $baseCats = self::find()->select('cat_id,cat_name,parent_id')
            ->where(['is_show'=>self::IS_SHOW,'parent_id'=>$pid])
            ->asArray()
            ->all();

        if(is_array($baseCats))
        {
            foreach ($baseCats as $key=>$value)
            {
                $baseCats[$key]['url'] = Url::to(['category/index','cid'=>$value['cat_id']]);
                $baseCats[$key]['son'] = self::getNavigation($value['cat_id']);
            }
        }
        return $baseCats;
    }

    /**
     * 根据ID查询分类信息
     *
     * @param $cid
     */
    static public function getCategoryInfo($cid)
    {
        return self::find()->select('cat_id,cat_name,parent_id')
            ->where('cat_id=:cid',[':cid'=>$cid])
            ->asArray()
            ->one();
    }


    /**
     * 获取分类下面包屑导航
     *
     * @param $cid
     * @return string
     */
    static public function getBreadcrumb($cid,$trail='')
    {
        $parents = self::getParentsCategory($cid);
        $breadUrl = '<a href="'.Tools::buildUrl(['index/index']).'">首页 </a>';

        while($catInfo = array_pop($parents))
        {
            $breadUrl .= '&rsaquo; <a href="'.Tools::buildUrl(['category/index','cid'=>$catInfo['cat_id']]).'">'.$catInfo['cat_name'].' </a>';
        }
        return empty($trail) ? $breadUrl : $breadUrl . '&rsaquo; &nbsp;&nbsp;' . $trail;
    }

    /**
     * 根据分类ID查询父级
     *
     * @param $cid
     * @return array
     */
    static function getParentsCategory($cid)
    {
        static $urHere = [];

        if($cid == self::BASE_CATE)
        {
            return $urHere;
        }
        $catInfo = self::getCategoryInfo($cid);
        $urHere[] = $catInfo;
        return self::getParentsCategory($catInfo['parent_id']);
    }


    /**
     * 查询指定分类下子分类并构造成In条件
     *
     * @param $catId
     * @return array
     */
    static function buildInCondition($catId)
    {

        $allCategories = self::find()->select('cat_id,parent_id,cat_name')->asArray()->all();
        $childs = self::getLevelCategories($allCategories,'',$catId);
        $cids = ArrayHelper::getColumn($childs,'cat_id');
        array_push($cids,$catId);

        return ['in','cat_id',$cids];
    }

    /**
     * 查询指定分类下搜索条件
     *
     * @param $cid
     * @return array
     */
    static function getFilter($cid)
    {

        // 查询品牌筛选条件
        $filterBrand = Goods::find()->select('b.brand_id,brand_name,count(1) AS num')
            ->joinWith('brand AS b',false,'INNER JOIN')
            ->where(self::buildInCondition($cid))
            ->andWhere(['is_delete'=>Goods::IS_NOT_DELETE,'is_on_sale'=>Goods::IS_ON_SALE])
            ->groupBy('b.brand_id')
            ->having('num>=1')
            ->orderBy(['b.sort'=>SORT_ASC])
            ->asArray()
            ->limit(20)
            ->all();

        // 标识选中的品牌
        $bids = Yii::$app->request->get('bid','');
        if(is_array($filterBrand) && !empty($bids))
        {
            foreach ($filterBrand as $key=>$value)
            {
                $filterBrand[$key]['checked'] = in_array($value['brand_id'],$bids);
            }
        }

        // 价格区间
        $filterPrice = Goods::find()
            ->select('floor(MIN(shop_price)) AS min_price,round(MAX(shop_price)) AS max_price ')
            ->where(self::buildInCondition($cid))
            ->andWhere(['is_delete'=>Goods::IS_NOT_DELETE,'is_on_sale'=>Goods::IS_ON_SALE])
            ->asArray()
            ->one();

        // 单个商品时或多个商品价格区间相同时
        if($filterPrice['min_price'] == $filterPrice['max_price'])
        {
            $filterPrice['min_price'] = 1;
            $filterPrice['slider_value'] = '[1'.','.$filterPrice['max_price'].']';
        }
        else
        {
            $filterPrice['slider_value'] = '['.$filterPrice['min_price'].','.intval($filterPrice['max_price']/2).']';

        }

        return ['filterBrand'=>$filterBrand,'filterPrice'=>$filterPrice];
    }
}
