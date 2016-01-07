<?php
class RemoveUserAction extends CAction
{
    public function run()
    {
        $res = array( "result" => false , "msg" => Yii::t("common","Something went wrong!" ));
        if(@$_POST){
	        $userId=$_POST["userId"];
	        $userType=$_POST["userType"];
	        $parentId=$_POST["parentId"];
	        $parentType=$_POST["parentType"];
	        $connectType=$_POST["connectType"];
	        $removeMeAsAdmin=false;
			try {
				if($parentType==Organization::COLLECTION)
					$parentConnect="memberOf";
				else
					$parentConnect=$parentType;
				Link::disconnect($userId, $userType, $parentId, $parentType,Yii::app()->session['userId'], $parentConnect);
				Link::disconnect($parentId, $parentType, $userId, $userType,Yii::app()->session['userId'], $connectType);
				if($userId == Yii::app()->session["userId"]){
					$removeMeAsAdmin=true;
				}
				$res = array( "result" => true , "msg" => Yii::t("",$connectType." successfully removed"), "collection" => $userType,"removeMeAsAdmin"=> $removeMeAsAdmin);			
			} catch (CTKException $e) {
				$res = array( "result" => false , "msg" => $e->getMessage() );
			}
		} 
		return Rest::json($res);
    }
}