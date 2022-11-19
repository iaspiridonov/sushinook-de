<?php
namespace Src\Controller\Cms;
use Core\Middleware\Controller;
use Src\Controller\Cms;
use Core\Facade\Template;
use Core\Service\Locale;
use Core\Service\Registry;
use Core\Gateway\Subjects;
use Src\Model;
use Core\Gateway;


class Parser extends Controller {
    protected $auth;

    public $categories;

    protected function bootProcess()
    {
        $this->middleware(($this->auth = new Cms));

    }

    protected function bootRouting()
    {
        $this->GET('/', 'loadPage');
        $this->POST('/', 'load');
        $this->GET('/test', 'test');
    }

    public function test() {
        $text = '40(1)';

        preg_match_all('/(.*)\((\d+)\)/',$text,  $matches);
        var_dump($matches[1][0]);
        var_dump($matches[2][0]);

        return $this->html('kek');
    }

    protected function bootDispatch()
    {
        Template::defaults([
            'Auth' => $this->auth,
            'alerts' => Registry::pull('session.alerts'),
            'path' => [
                'controller' => $this->path(),
                'uri' => $this->path(Registry::get('http.request.path'))
            ]
        ]);
    }

    public function loadPage() {
        return $this->html( Template::render('cms/module/parser/index'));
    }

    public function getAvailableCats() {
        $out = [];

        $cats = Subjects::of('Category')->select(['is_related'=>0])->get();

        foreach($cats as $cat) {
            $out[$cat->name] = $cat;

            if(!empty($cat->categories)) {
                foreach($cat->categories as $subCat) {
                    $out[$cat->name.'|'.$subCat->name] = $subCat;
                }
            }
        }
        return $out;
    }

    public function load() {
        $request = Registry::get('http.request.files.file');
        $tmp_path = $request->getStream()->getMetadata()['uri'];
        $availableCats = $this->getAvailableCats();
        $articul_is_parsed = [];
        if ( $xlsx = SimpleXLSX::parse( $tmp_path ) ) {
            foreach ( $xlsx->rows() as $r => $row ) {
               if($r==0) continue;
               $code = $row[0];
               $articul = trim($row[1]);
               $category = trim($row[2]);
               $productName = $row[3];
               $description = $row[4];
               $characteristics_str = trim($row[5]);
               $price = $row[6];
               $sizes = $row[7];
               $novetly = intval($row[8]);
               $sale = intval($row[9]);


               $articul_is_parsed[] = $articul;
               $characteristicsArr = [];

               $characteristics = explode(";", $characteristics_str);
               foreach($characteristics as $characteristic) {
                   list($key, $val)  = explode("|", $characteristic);
                   $characteristicsArr[$key] = $val;
               }
               $sizesArr = [];
               $sizes = explode(";", $sizes);
               if(count($sizes)) {
                   foreach($sizes as $size) {
                       preg_match_all('/(.*)\((\d+)\)/',$size,  $matches);
                       $s = $matches[1][0];
                       $v = $matches[2][0];

                       $sizesArr[$s] = $v;
                    }
               }


                if(!isset($availableCats[$category])) {
                    $cats_path = explode("|", $category);
                    $parent = false;
                    $curentPath = [];
                    foreach($cats_path as $catName) {
                        $curentPath[] = $catName;
                        if(isset($availableCats[join("|", $curentPath)])) {
                            $parent = $availableCats[$catName];
                        } else {
                            ($newCat = new Model\Category(['name'=>$catName]))->save();
                            if($parent !== false) {
                                $parent->categories()->save($newCat);
                            }
                            $parent = $newCat;
                            $availableCats[join("|", $curentPath)] = $newCat;
                        }
                    }
                }
                $categorySubject = $availableCats[$category];

                if($productSubject = Subjects::of('Product')->select(['articul'=>$articul])->first()) {
                    $productSubject->offsetSet('name', $productName);
                    $productSubject->offsetSet('description', $description);
                    $productSubject->offsetSet('price', $price);
                    $productSubject->offsetSet('novetly', $novetly);
                    $productSubject->offsetSet('sale', $sale);
                    $productSubject->save();
                } else {
                    ($productSubject = new Model\Product([
                        'name'=>$productName,
                        'articul'=>$articul,
                        'description'=>$description,
                        'price'=>$price,
                        'novetly'=>intval($novetly),
                        'sale'=>$sale]))
                        ->save();
                    $categorySubject->products()->save($productSubject);
                }
                $colorName = '';
                if(isset($characteristicsArr['Цвет'])) {
                    $colorName = $characteristicsArr['Цвет'];
                }
                if($colorSubject = Subjects::of('ProductColor')->select(['code'=>$code])->first()) {
                    //
                } else {
                    ($colorSubject = new Model\ProductColor(['name'=>$colorName, 'code'=>$code, 'articul'=>$articul]))->save();
                    $productSubject->colors()->save($colorSubject);
                }


                if(count($sizesArr)) {
                    foreach($sizesArr as $sizeName=>$sizeAmount) {

                        if($sizeSubject = Subjects::of('Size')->select(['name'=>$sizeName, 'code'=>$code])->first()) {
                            $sizeSubject->offsetSet('amount', $sizeAmount);
                            $sizeSubject->save();
                        } else {
                            ($sizeSubject = new Model\Size(['name'=>$sizeName, 'amount'=>$sizeAmount, 'code'=>$code]))->save();
                            $colorSubject->sizes()->save($sizeSubject);
                        }
                    }
                }



                if(count($characteristicsArr)) {
                    $ch_arr = $characteristicsArr;
                    if(isset($ch_arr['Цвет']))
                        unset($ch_arr['Цвет']);
                    $characteristic_hash = md5(join("", $ch_arr));


                    if($productSubject->characteristic_hash != $characteristic_hash) {

                        if(!empty($productSubject->characteristics)) {
                            foreach($productSubject->characteristics as $ch) {
                                $ch->delete();
                            }
                        }

                        foreach($characteristicsArr as $name=>$value) {
                            if($characteristicSubject = Subjects::of('Characteristic')->select(['name'=>$name,'articul'=>$articul])->first()) {
                                $characteristicSubject->offsetSet('value', $value);
                                $characteristicSubject->save();
                            } else {
                                ($newCharacteristic = new Model\Characteristic(['name'=>$name, 'value'=>$value, 'md5'=>md5($name)]))->save();
                                $productSubject->characteristics()->save($newCharacteristic);
                            }
                        }
                    }
                    $productSubject->offsetSet('characteristic_hash', $characteristic_hash);
                    $productSubject->save();
                }


            }
        } else {
            echo SimpleXLSX::parseError();
        }
        return $this->html('finish');

    }
}

?>