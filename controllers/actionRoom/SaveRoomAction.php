<?php
/**
 * @return [json] 
 */
class SaveRoomAction extends CAction
{
    public function run()
    {
        $res = array();
        if( Yii::app()->session["userId"] )
        {
            $email = $_POST["email"];
            $name  = $_POST['name'];
            //if exists login else create the new user
            if(PHDB::findOne (Person::COLLECTION, array( "email" => $email ) ))
            {
                //udate the new app specific fields
                $newInfos = array();
                $newInfos['email'] = (string)$email;
                $newInfos['name'] = (string)$name;
                $newInfos['type'] = $_POST['type'];
                if( isset( $_POST["parentType"] ) ) 
                    $newInfos['parentType'] = $_POST['parentType'];
                if( isset( $_POST["parentId"] ) ) 
                    $newInfos['parentId'] = $_POST['parentId'];
                
                if( isset($_POST['tags']) && count($_POST['tags']) )
                    $newInfos['tags'] = $_POST['tags'];
                
                $newInfos['created'] = time();
                PHDB::insert( Survey::PARENT_COLLECTION, $newInfos );
                /*PHDB::updateWithOptions( Survey::PARENT_COLLECTION,  array( "name" => $name ), 
                                                   array('$set' => $newInfos ) ,
                                                   array('upsert' => true ) );
                */
                $res['result'] = true;
                $res['msg'] = "survey Room Saved";
                $res["savingTo"] = Survey::PARENT_COLLECTION;
                $res["newInfos"] = $newInfos;
            }else
                $res = array('result' => false , 'msg'=>"user doen't exist");
        } else
            $res = array('result' => false , 'msg'=>'something somewhere went terribly wrong');
            
        Rest::json($res);  
        Yii::app()->end();
    }
}