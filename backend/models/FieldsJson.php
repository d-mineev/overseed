<?php

namespace backend\models;

use Yii;
use yii\db\Query;

/**
 * Модель для создания json файла полей
 */
class FieldsJson extends Fields
{
    /**
     * Создание json файла полей
     *
     * @return string
     */
    public function makeJson ($fieldsfind)
    {
        $jsonFileName = 'fields_' . microtime(true) . '.json';

        $fields = $this->prepareFields($fieldsfind);

        file_put_contents(Yii::$app->params['files']['data'] . $jsonFileName, json_encode($fields));

        return $jsonFileName;
    }

    /**
     * @return array
     */
    private function prepareFields ($fieldsfind)
    {
        /** @var Fields[] $fields */

        $query = new Query();
        $query
            ->select(['id', 'name', 'coordinates'])
            ->from(Fields::tableName());

        if (!empty($fieldsfind)) {
            $query->andWhere(['id' => $fieldsfind]);
            $fields = $query->all();
            $result = [];
            foreach ($fields as $field) {
                $result[$field['id']] = [
                    'name' => $field['name'],
                    'points' => $field['coordinates'],
                ];
            }
            return $result;
        } else {
            $user = Yii::$app->user;
            if (!$user->can('usersview')){
                if (!empty($user->identity->fieldsrules)) {
                    $objects = Json_decode($user->identity->fieldsrules);
                    $ob = [];
                    foreach ($objects as $key => $value){
                        if ($value){
                            $ob[] = (int) $key;
                        }
                        //  $query->andWhere(['id' => $ob]);

                    }
                    $query->andWhere(['id' => $ob]);
                    $fields = $query->all();

                    $result = [];
                    foreach ($fields as $field) {
                        $result[$field['id']] = [
                            'name' => $field['name'],
                            'points' => $field['coordinates'],
                        ];
                    }
                    return $result;

                } else {

                }

            } else {
                $fields = $query->all();

                $result = [];
                foreach ($fields as $field) {
                    $result[$field['id']] = [
                        'name' => $field['name'],
                        'points' => $field['coordinates'],
                    ];
                }
                return $result;
            }
        };

    }
}
