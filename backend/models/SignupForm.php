<?php
namespace backend\models;

use common\models\User;
use yii\base\Model;
use yii\db\Query;
use Yii;

/**
 * Signup form
 */
class SignupForm extends Model
{
    public $username;
    public $name;
    public $email;
    public $password;
    public $role;
    public $telefon;
    public $skype;
    public $position;
    public $objectsrules;
    public $fieldsrules;
    public $treilersrules;
    public $driversrules;
    public $group;
    private $_user;
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['username', 'filter', 'filter' => 'trim'],
            ['username', 'required'],
            ['username', 'unique', 'targetClass' => '\common\models\User', 'message' => 'This username has already been taken.'],
            ['username', 'string', 'min' => 6, 'max' => 255],

            ['email', 'filter', 'filter' => 'trim'],
            ['email', 'required'],
            ['email', 'email'],
            ['email', 'string', 'max' => 255],
            ['email', 'unique', 'targetClass' => '\common\models\User', 'message' => 'This email address has already been taken.'],

            ['password', 'required'],
            ['password', 'string', 'min' => 6],
        ];
    }

    /**
     * Signs user up.
     *
     * @return User|null the saved model or null if saving fails
     */
    public function signup($status)
    {


        if (!$this->validate()) {
            return null;
        }

        $user = new User();
        $user->username = $this->username;
        $user->email = $this->email;
        $user->setPassword($this->password);
        $user->generateAuthKey();
        $user->name = $this->name;
        $user->position = $this->position;
        $user->telefon = $this->telefon;
        $user->skype = $this->skype;
        $user->objectsrules = $this->objectsrules;
        $user->fieldsrules = $this->fieldsrules;
        $user->treilersrules = $this->treilersrules;
        $user->driversrules = $this->driversrules;
        $user->role = $this->role;
        $user->group = $this->group;
        $user->status = (int) $status;

        $userOnline = Yii::$app->user;
        if (empty($userOnline->identity->parenttree)){
            $parenttree = "|".$userOnline->identity->id."|";
        } else {
            $parenttree = $userOnline->identity->parenttree . $userOnline->identity->id."|";
        }
        $user->parenttree = $parenttree;

        return $user->save() ? $user : null;

/*

// нужно добавить следующие три строки:
        $auth = Yii::$app->authManager;
        $authorRole = $auth->getRole('author');
        $auth->assign($authorRole, $user->getId());

        return $user;

*/
    }

    public function userupdate($id , $status)
    {
        if (
                Yii::$app->db->createCommand()->update('user',
                    [
                        'username'        =>  $this->username,
                        'name'        =>  $this->name,
                        'email'      =>  $this->email,
                        'position' =>  $this->position,
                        'telefon'      =>  $this->telefon,
                        'skype'     =>  $this->skype,
                        'objectsrules' => $this->objectsrules,
                        'fieldsrules' => $this->fieldsrules,
                        'treilersrules' => $this->treilersrules,
                        'driversrules' => $this->driversrules,
                        'role' => $this->role,
                        'group' => $this->group,
                        'status' => (int) $status

                    ], 'id ='.$id)->execute()
            )   {
            return true;

                }
        else {
            return false;
        }

    }

    public function usersetpassword($id , $password)
    {

        $user = User::findOne(['id' => $id]);

        $user -> setPassword($password);
        return $user->save(false)? true : false;

    }

    public function getall(){

        $query = new Query();

         $query
            ->select(['id', 'username', 'name',  'email', 'position' , 'telefon' , 'skype' , 'status'])
            ->from(User::tableName());
        if (!Yii::$app->user->can('superadmin')) {
            $query->Where(['like', 'parenttree', '|' . Yii::$app->user->identity->id . '|']);
            $query->orWhere(['id' => Yii::$app->user->identity->id]);
        }
        $fields =  $query->orderBy('id')->all();

        return $fields;

    }

    public function getOne($id){

        $query = new Query();
        $query->select([  'id', 'username', 'name', 'email', 'position' ,
                        'telefon' , 'skype' , 'status', 'foto',
                        'objectsrules', 'fieldsrules', 'treilersrules', 'driversrules',
                        'role' , 'group'
                    ])
            ->from(User::tableName())
            ->where(['id' => $id]);
        if (!Yii::$app->user->can('superadmin')) {
            $query->Where(['like', 'parenttree', '|' . Yii::$app->user->identity->id . '|']);
            $query->orWhere(['id' => Yii::$app->user->identity->id]);
        }

        return $query->all();
    }

    public function delete($id)
    {

        if (
        Yii::$app->db
            ->createCommand()
            ->delete('user', ['id' => $id])
            ->execute()
        )   {
            return true;

        }
        else {
            return false;
        }

    }

    public function getallbyroles($id){
        $query = new Query();
        $fields = $query
            ->select(['id'])
            ->from(User::tableName())
            ->where(['role' => $id])
            ->all();
        return $fields;
    }

    public function upUsersInGroup($id, $data){

        $objectsrules = (empty($data['data']['checkboxobject'])?'{}':json_encode($data['data']['checkboxobject']));
        $fieldsrules = (empty($data['data']['checkboxfield'])?'{}':json_encode($data['data']['checkboxfield']));
        $treilersrules = (empty($data['data']['checkboxtreiler'])?'{}':json_encode($data['data']['checkboxtreiler']));
        $driversrules = (empty($data['data']['checkboxdriver'])?'{}':json_encode($data['data']['checkboxdriver']));

       $update =  Yii::$app->db->createCommand()->update('user',
            [
                'objectsrules' => $objectsrules,
                'fieldsrules' => $fieldsrules,
                'treilersrules' => $treilersrules,
                'driversrules' => $driversrules
            ], '"group" ='.(int)$id)->execute();

            if ($update >= 0)   {
                return true;
            }
            else {
                return false;
            }



        return true;

    }




}
