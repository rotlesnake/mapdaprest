<?php

namespace MapDapRest;

use \Illuminate\Database\Eloquent\Model as EloquentModel;


use \MapDapRest\Models\SystemLogs as Logs;


class Model extends EloquentModel
{



    public static function boot()
    {
        parent::boot();
        
        
        static::creating(function($model) {
	  $app = \MapDapRest\App::getInstance();
          if ($app->auth->isGuest()) { throw new \Exception('user not found'); }
	  
          $model->created_by_user = $app->auth->getFields()['id'];
          return $model->beforeAdd($model);
        });
        static::created(function($model) {
	  $app = \MapDapRest\App::getInstance();
          if ($app->auth->isGuest()) { throw new \Exception('user not found'); }
	  
          $log = new Logs();
          $log->user_id = $app->auth->getFields()['id'];
          $log->table_name = $model->table;
          $log->row_id = $model->id;
          $log->action = 1;
          $log->fields = $model;
          $log->save();
          $model->afterAdd($model);
        });


        static::updating(function($model) {
          return $model->beforeEdit($model);
        });
        static::updated(function($model) {
	  $app = \MapDapRest\App::getInstance();
          if ($app->auth->isGuest()) { throw new \Exception('user not found'); }

          $log = new Logs();
          $log->user_id = $app->auth->getFields()['id'];
          $log->table_name = $model->table;
          $log->row_id = $model->id;
          $log->action = 2;
          $log->fields = $model;
          $log->save();
          $model->afterEdit($model);
        });
        

        static::deleting(function($model) {
          return $model->beforeDelete($model);
        });
        static::deleted(function($model) {
	  $app = \MapDapRest\App::getInstance();
          if ($app->auth->isGuest()) { throw new \Exception('user not found'); }

          $log = new Logs();
          $log->user_id = $app->auth->getFields()['id'];
          $log->table_name = $model->table;
          $log->row_id = $model->id;
          $log->action = 3;
          $log->fields = $model;
          $log->save();
          $model->afterDelete($model);
        });
        

       
    }//boot------------------------------------



}
