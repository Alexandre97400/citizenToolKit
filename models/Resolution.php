<?php

class Resolution
{
    const COLLECTION = "resolutions";
    const CONTROLLER = "resolution";

    const STATUS_AMENDABLE  = "amendable";
    const STATUS_TOVOTE     = "tovote";
    const STATUS_CLOSED     = "closed";
    const STATUS_ARCHIVED   = "archived";

    public static $dataBinding = array (
        
        "title"                 => array("name" => "title"),
        "shortDescription"      => array("name" => "shortDescription"),
        "description"           => array("name" => "description",           "rules" => array("required")),
        "arguments"             => array("name" => "arguments"),
        "tags"                  => array("name" => "tags"),
        "urls"                  => array("name" => "urls"),
        
        // true / false
        "amendementActivated"   => array("name" => "amendementActivated",   "rules" => array("required")),
        "amendementDateEnd"     => array("name" => "amendementDateEnd"),
        "durationAmendement"    => array("name" => "durationAmendement"),
        
        // true / false
        "voteActivated"         => array("name" => "voteActivated",         "rules" => array("required")),
        "voteDateEnd"           => array("name" => "voteDateEnd"),
        
        // Amendable / ToVote / Closed / Archived
        "status"                => array("name" => "status",                "rules" => array("required")), 
        
        // 50%  / 75% / 90%
        "majority"              => array("name" => "majority",              "rules" => array("required")),

        // true / false
        //"canModify"           => array("name" => "canModify",             "rules" => array("required")), 
        "viewCount"             => array("name" => "viewCount"),

        //"idUserAuthor"            => array("name" => "idUserAuthor",          "rules" => array("required")),
        "idParentRoom"          => array("name" => "idParentRoom",          "rules" => array("required")),
        "parentId"              => array("name" => "parentId",              "rules" => array("required")),
        "parentType"            => array("name" => "parentType",            "rules" => array("required")),
       
        "amendements"           => array("name" => "amendements"),
        
        "modified" => array("name" => "modified"),
        "updated" => array("name" => "updated"),
        "creator" => array("name" => "creator"),
        "created" => array("name" => "created"),

        //"medias" => array("name" => "medias"),
    );

    public static function getDataBinding() {
        return self::$dataBinding;
    }

    public static function getById($id) {
        $survey = PHDB::findOneById( self::COLLECTION , $id );
        return $survey;
    }

    public static function getSimpleSpecById($id, $where=null, $fields=null){
        if(empty($fields))
            $fields = array("_id", "name");
        $where["_id"] = new MongoId($id) ;
        $resolution = PHDB::findOne(self::COLLECTION, $where ,$fields);
        return @$resolution;
    }

    public static function getAllVoteRes($proposal){
        $voteRes = array("up"=> array("bg-color"=> "green-k",
                                        "voteValue"=>"up"),
                        "down"=> array("bg-color"=> "red",
                                        "voteValue"=>"down"),
                        "white"=> array("bg-color"=> "white",
                                        "voteValue"=>"white"),
                        "uncomplet"=> array("bg-color"=> "orange",
                                        "voteValue"=>"uncomplet"),

        );

        $votes = @$proposal["votes"] ? $proposal["votes"] : array();

        if(!@$votes["up"]) $votes["up"] = array();
        if(!@$votes["down"]) $votes["down"] = array();
        if(!@$votes["white"]) $votes["white"] = array();
        if(!@$votes["uncomplet"]) $votes["uncomplet"] = array();

        //$voteRes = array("up"=>array(), );

        $totalVotant = 0;
        foreach ($votes as $key => $value) {
            $voteRes[$key]["votant"] = count($votes[$key]);
            $totalVotant+=count($votes[$key]);
        } //echo $totalVotant; exit;
        foreach ($votes as $key => $value) {
            $voteRes[$key]["percent"] = $totalVotant > 0 ? $voteRes[$key]["votant"] * 100 / $totalVotant : 0;
        }

        return $voteRes;
    }
    public static function getTotalVoters($proposal){
        if(!@$proposal["votes"]) return 0;
        $totalVotant = 0;
        foreach ($proposal["votes"] as $key => $value) {
            $totalVotant+=count($value);
        }
        return $totalVotant;
    }
}

?>