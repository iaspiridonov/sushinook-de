<?php namespace Src\Controller\Cabinet;

use Core\Facade\Template;
use Core\Gateway\Subjects;
use Core\Service\Form;
use Core\Service\Registry;
use Core\Service\Translation;
use Src\Model\Product;
use Src\Model\Characteristic;
use Src\Model\Photo;
use Core\Gateway;

class Products extends AbstractController
{
    protected function bootRouting()
    {
        $this->GET('/add', 'addPage');
        $this->POST('/add', 'add');
        $this->GET('/list', 'listPage');
        $this->GET('/edit/{id:\d+}', 'editPage');
        $this->POST('/edit/{id:\d+}', 'edit');
        $this->POST('/remove/{id:\d+}', 'remove');
    }

    public function remove($id) {
        $user = $this->auth->user();
        $product = Subjects::of('Product')->find($id);
        if($user->id != $product->shop->id) {
            return false;
        }

        $product->delete();

        return $this->json(
            [
                'successfully'=>true,
                'text'=>'Товар успешно удален!'
            ]
        );
    }

    public function edit($id) {
        $user = $this->auth->user();
        $request = Registry::get('http.request.body');
        $product = Subjects::of('Product')->find($id);
        if($user->id != $product->shop->id) {
            return $this->redirect('/ru/cabinet/products/list');
        }
        $category = Subjects::of('Category')->find($request['category']);
        // удаляю с предыдущей категории
        if(!!($relations = Gateway\Relations::of('Category')->select(['relation' => 'products', 'subject_id' => $product->parent->id, 'relation_subject_id' => [$product->id]])->get()) && $relations->count()) {
            $relations->each(function ($relation) {
                $relation->delete();
            });
        }

        //var_dump($product->parent->id);
        //var_dump($product->id);

        $category->products()->save($product);
        //return $this->html('d');

        $product->offsetSet('name', $request['name']);
        $product->offsetSet('price_old', ($request['price_discount']?$request['price']:0));
        $product->offsetSet('price', ($request['price_discount']?$request['price_discount']:0));
        $product->offsetSet('sale', (isset($request['sale'])?1:0));
        $product->offsetSet('novetly', (isset($request['novetly'])?1:0));
        $product->offsetSet('description', $request['description']);

        $product->save();

        $product->characteristics->each(function ($subject) {
            $subject->delete();
        });


        foreach($request['characteristics']['name'] as $i=>$name) {
            $value = $request['characteristics']['value'][$i];
            if(empty($name)) continue;
            ($newCharacteristic = new Characteristic(['name'=>$name, 'value'=>$value]))->save();
            $product->characteristics()->save($newCharacteristic);
        }

       // $category->products()->save($product);

        $images = Registry::get('http.request.files.images');
        foreach($images as $image) {
          if($image->getError() != 4) { //is not uploaded
                if(!empty($user->avatar)) {
                    unlink(PUBLIC_PATH.$user->avatar);
                }
                $imagePath = $this->uploadImage($image);

                ($newImage = new Photo(['src'=>$imagePath]))->save();
                $product->images()->save($newImage);
            }  
        }
        

        if(!count($errors)) {
            $user->save();
            return $this->json(
                [
                    'successfully'=>true,
                    'text'=>'Товар успешно изменен!'
                ]
            );
        } else {
            return $this->json(
                [
                    'successfully'=>false,
                    'text'=>join("<br>", $errors)
                ]
            );
        }



    }

    public function editPage($id) {
        $user = $this->auth->user();
        $pageTitle = Translation::of('Edit products');
        $breadcrumbs = [''=>$pageTitle];    
        $product = Subjects::of('Product')->find($id);
        if($user->id != $product->shop->id) {
            return $this->redirect('/ru/cabinet/products/list');
        }
        $categories = Subjects::of('Category')->with('categories')->select(['is_related'=>0])->get();
        return $this->html(Template::render('cabinet/products/add',
                                            [
                                                'breadcrumbs'=>$breadcrumbs,
                                                'pageTitle'=>$pageTitle,
                                                'categories'=>$categories,
                                                'product'=>$product
                                            ]));
    }

    public function listPage() {
        $user = $this->auth->user();
        $pageTitle = Translation::of('Your products');
        $breadcrumbs = [''=>$pageTitle];    
        $products = $user->products()->paginate(12);
        return $this->html(Template::render('cabinet/products/list',
                                            [
                                                'breadcrumbs'=>$breadcrumbs,
                                                'pageTitle'=>$pageTitle,
                                                'products'=>$products
                                            ]));   
    }

    public function addPage()
    {
        $pageTitle = Translation::of('Add products');
        $breadcrumbs = [''=>$pageTitle];
        $categories = Subjects::of('Category')->with('categories')->select(['is_related'=>0])->get();
        return $this->html(Template::render('cabinet/products/add',
                                            [
                                                'breadcrumbs'=>$breadcrumbs,
                                                'pageTitle'=>$pageTitle,
                                                'categories'=>$categories
                                            ]));
    }

    public function add() {
        $errors = [];
        $user = $this->auth->user();
        $request = Registry::get('http.request.body');
        $category = Subjects::of('Category')->find($request['category']);

        $newProduct = new Product(
                                    [
                                        'name'=>$request['name'],
                                        'price_old'=>($request['price_discount']?$request['price']:0),
                                        'price'=>($request['price_discount']?$request['price_discount']:0),
                                        'sale'=>(isset($request['sale'])?1:0),    
                                        'novetly'=>(isset($request['novetly'])?1:0),
                                        'description'=>$request['description'],
                                    ]
                                 );
        $newProduct->save();
        $user->products()->save($newProduct);
        foreach($request['characteristics']['name'] as $i=>$name) {
            $value = $request['characteristics']['value'][$i];
            if(empty($name)) continue;
            ($newCharacteristic = new Characteristic(['name'=>$name, 'value'=>$value]))->save();
            $newProduct->characteristics()->save($newCharacteristic);
        }

        $category->products()->save($newProduct);

        $images = Registry::get('http.request.files.images');
        foreach($images as $image) {
          if($image->getError() != 4) { //is not uploaded
                if(!empty($user->avatar)) {
                    unlink(PUBLIC_PATH.$user->avatar);
                }
                $imagePath = $this->uploadImage($image);

                ($newImage = new Photo(['src'=>$imagePath]))->save();
                $newProduct->images()->save($newImage);
            }  
        }
        

        if(!count($errors)) {
            $user->save();
            return $this->json(
                [
                    'successfully'=>true,
                    'text'=>'Товар успешно добавлен!'
                ]
            );
        } else {
            return $this->json(
                [
                    'successfully'=>false,
                    'text'=>join("<br>", $errors)
                ]
            );
        }
    }


    private function uploadImage($file) {
        $folder = 'images';
        $filePath = '/uploads/' . $folder . '/' . date('Y') . '/' . date('m') . '/' . date('d');
        $uploadPath = PUBLIC_PATH . $filePath;

        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }

        $key = sha1(uniqid('', true));
        $fileName = 'f_' . $key . substr($file->getClientFilename(), strrpos($file->getClientFilename(), '.'));
        $file->moveTo($uploadPath.'/'.$fileName);

        return $filePath.'/'.$fileName;
    }
}
?>