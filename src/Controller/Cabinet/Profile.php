<?php namespace Src\Controller\Cabinet;

use Core\Facade\Template;
use Core\Gateway\Subjects;
use Core\Service\Registry;
use Core\Service\Translation;
use Src\Model\ClientAddress;


class Profile extends AbstractController
{
    protected function bootRouting()
    {
        $this->GET('/', 'indexPage');
        $this->POST('/', 'saveProfile');
    }

    public function indexPage()
    {
        $maxlifetime = ini_get("session.gc_maxlifetime");
        $user = $this->auth->user();
        $pageTitle = Translation::of('Data of company');
        $breadcrumbs = [''=>$pageTitle];
        $activities = Subjects::of('Activity')->get();
        return $this->html(Template::render('cabinet/profile/edit',
                                            [
                                                'breadcrumbs'=>$breadcrumbs,
                                                'pageTitle'=>$pageTitle,
                                                'activities'=>$activities
                                            ]));
    }

    public function saveProfile()
    {
        $errors = [];
        $user = $this->auth->user();
        $request = Registry::get('http.request.body');
        $avatarFile = Registry::get('http.request.files.avatar');
        if($avatarFile->getError() != 4) { //is not uploaded
            if(!empty($user->avatar)) {
                unlink(PUBLIC_PATH.$user->avatar);
            }
            $avatarFilePath = $this->uploadAvatar($avatarFile);
            $user->offsetSet('avatar', $avatarFilePath);
        }

        $backgroundFile = Registry::get('http.request.files.background');
        if($backgroundFile->getError() != 4) { //is not uploaded
            if(!empty($user->background)) {
                unlink(PUBLIC_PATH.$user->background);
            }
            $backgroundFilePath = $this->uploadAvatar($backgroundFile);
            $user->offsetSet('background', $backgroundFilePath);
        }

        $user->offsetSet('category', $request['activity']);
        if(!empty($request['name'])) {
            $user->offsetSet('name', $request['name']);
        } else {
            $errors[] = 'Заполните поле "Название компании"';
        }
        $user->offsetSet('about', $request['about']);
        $user->offsetSet('company_url', $request['company_url']);

        $oldAddressSubjects = $user->addresses;
        $oldAddressSubjects->each(function ($subject) {
            $subject->delete();
        });
        if(count($request['address'])) {
            foreach($request['address']['floor'] as $i=>$address) {
                if(!empty($address)) {
                    ($newAddress = new ClientAddress(['floor'=>$address,'boutique'=>$request['address']['boutique'][$i]]));
                    $user->addresses()->save($newAddress);
                }
            }
        }


        if(count($request['phones'])) {
            foreach($request['phones'] as $i=>$phone) {
                if(empty($phone))
                    unset($request['phones'][$i]);
            }
            $user->offsetSet('phones', join("\n", $request['phones']));
        }

        if(count($request['instagram'])) {
            foreach($request['instagram'] as $i=>$phone) {
                if(empty($phone))
                    unset($request['instagram'][$i]);
            }
            $user->offsetSet('instagram', join("\n", $request['instagram']));
        }
        if(count($request['facebook'])) {
            foreach($request['facebook'] as $i=>$phone) {
                if(empty($phone))
                    unset($request['facebook'][$i]);
            }
            $user->offsetSet('facebook', join("\n", $request['facebook']));
        }
        if(count($request['vk'])) {
            foreach($request['vk'] as $i=>$phone) {
                if(empty($phone))
                    unset($request['vk'][$i]);
            }
            $user->offsetSet('vk', join("\n", $request['vk']));
        }
        if(count($request['youtube'])) {
            foreach($request['youtube'] as $i=>$phone) {
                if(empty($phone))
                    unset($request['youtube'][$i]);
            }
            $user->offsetSet('youtube', join("\n", $request['youtube']));
        }


        

        if(!count($errors)) {
            $user->save();
            return $this->json(
                [
                    'successfully'=>true,
                    'text'=>'Ваши изменения успешно сохранены!'
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

    private function uploadAvatar($file) {
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