<?php
use yii\widgets\Pjax;
use yii\bootstrap\ActiveForm;
use yii\widgets\LinkPager;
use yii\widgets\ListView;
use yii\helpers\Url;
use kartik\select2\Select2;
use frontend\widgets\Breadcrumbs;

$this->title = $cat->name;
?>
<div class="content content-lot-view-one content-lot-index ">
    <div class="container">

        <?= Breadcrumbs::widget(['params' => $params]); ?>

        <div class="row row-m-r-l-0">


            <div class="col-xs-w col-width-220">
                <?php $form = ActiveForm::begin(['id' => 'form-filter', 'method' => 'GET', 'options' => ['data-pjax' => true]]); ?>
                <?= $this->render('filter', ['form' => $form, 'model' => $searchModel, 'cat' => $cat, 'filter' => $filter]); ?>
                <?php ActiveForm::end(); ?>
            </div>

            <?= $this->render('view_start', ['start' => $start, 'banners' => $banners]); ?>

            <div class="col-xs-w col-width-765 col-m-l-35 all_products">

                <?php Pjax::begin(['enablePushState' => true, 'enableReplaceState' => true, 'formSelector' => '#form-filter', 'id' => 'search-count', 'timeout' => 3000]); ?>
                <header class="ctg-header">
                    <h2>Найдено: <?= $dataProvider->getTotalCount() ?></h2>
                    <span class="subscribe-box">
						<a class="add-subscribe" data-pjax="0"><i class="fa fa-plus"></i></a>
						<span>подписаться на поисковый запрос</span>
					</span>
                </header>

                <nav class="nav-sort">
                    Сортировать по:
                    <?= Select2::widget([
                        'name' => 'ProductSearch[sort]',
                        'data' => [
                            'price' => 'Стоимости',
                        ],
                        'options' => [
                            'id' => 'sort_select',
                            'class' => 'form-control'
                        ],
                        'pluginOptions' => [
                            'minimumResultsForSearch' => 'Infinity'
                        ],
                    ]); ?>

                    <a class="sort-b <?= !empty($params['direction']) && $params['direction'] == 'asc' ? 'active' : '' ?>"
                       href="#" onclick="$('#sort_filter').val('asc').change();return false;">По возрастанию</a>
                    <a class="sort-b <?= !empty($params['direction']) && $params['direction'] == 'desc' ? 'active' : '' ?>"
                       href="#" onclick="$('#sort_filter').val('desc').change();return false;">По убыванию</a>
                    <span class="nav-view">
                        <span>Вид:</span>
                        <a class="filterview filter_row <?= $view == 'row' ? 'active' : '' ?>" href="#"><i
                                    class="ion ion-grid"></i></a>
                        <a class="filterview filter_list <?= $view == 'list' ? 'active' : '' ?>" href="#"><i
                                    class="ion ion-navicon-round"></i></a>
                    </span>
                </nav>

                <section class="block <?= $view == 'list' ? 'ctg-list' : 'ctg-list-row' ?>">

                    <?= ListView::widget([
                        'dataProvider' => $dataProvider,
                        'itemView' => '_' . $view,
                        'layout' => '<ul class="ul-box">{items}</ul>{pager}',
                        'pager' => [
                            'linkOptions' => [
                                'class' => "filter-pagination"
                            ],
                            'maxButtonCount' => 5,
                            'firstPageLabel' => false,
                            'lastPageLabel' => false,
                            'prevPageLabel' => '<span class="ion ion-arrow-left-c"></span>',
                            'nextPageLabel' => '<span class="ion-arrow-right-c"></span>',
                        ],

                        'itemOptions' => [
                            'tag' => 'li',
                            'class' => 'lots',
                        ],

                    ]);
                    ?>
                </section>
                <?php Pjax::end(); ?>
            </div>
        </div>
    </div>
</div>

<div id="error-modal" class="modal">
    <button type="button" class="close" onclick="Custombox.close();">
        <i class="ion ion-close"></i>
    </button>
    <div class="modal-content">
        <h3>Укажите причину</h3>
        <form class="form-horizontal form-error-msg" role="form">
            <input type="hidden" name="url_error" value="<?= Url::to('') ?>">
            <div class="form-group field-type-error">
                <div class="col-xs-12">
                    <label class="radio-horizontal">
                        <input type="radio" name="type_error" value="1">
                        <span>Нет в наличии</span>
                    </label>
                    <label class="radio-horizontal">
                        <input type="radio" name="type_error" value="2">
                        <span>Неверная цена</span>
                    </label>
                    <label class="radio-horizontal">
                        <input type="radio" name="type_error" value="3">
                        <span>Неверная категория</span>
                    </label>
                    <label class="radio-horizontal">
                        <input type="radio" name="type_error" value="4">
                        <span>Другое</span>
                    </label>
                </div>
            </div>
            <div class="form-group field-text-error">
                <div class="col-xs-12">
                    <textarea rows="8" name="text_error" class="form-control"
                              placeholder="Введите текст вашего сообщения"></textarea>
                </div>
            </div>
            <div class="row">
                <div class="col-xs-12">
                    <p class="form-text-error-send text-danger"></p>
                </div>
            </div>
            <div class="form-group">
                <div class="col-xs-12">
                    <button class="btn btn-default form-error-btn">Отправить</button>
                </div>
            </div>
        </form>
    </div>
</div>
