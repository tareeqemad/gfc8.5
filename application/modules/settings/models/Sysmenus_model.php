<?php
 if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Created by PhpStorm.
 * User: Ahmed Barakat
 * Date: 9/1/14
 * Time: 1:18 PM
 */

class Sysmenus_model extends MY_Model{

    // Raw menu rows are reused only by this model instance in the current request.
    private $menuListMemo = array();
    private $menuListBuckets = array();

    public function clearMenuListMemo(){
        $this->menuListMemo = array();
        $this->menuListBuckets = array();
    }

    /**
     * @return array
     *
     * return all system_menus data ..
     */

    function __construct(){
        parent::__construct();
        $this->load->model('Root/New_rmodel');
    }
    
    function getAll(){

        $params =array(

            array('name'=>':GET_ROW_LOG','value'=>'cursor','type'=>OCI_B_CURSOR),
            array('name'=>':MSG_OUT','value'=>'MSG_OUT','type'=>SQLT_CHR,'length'=>-1)
        );

        $result = $this->New_rmodel->general_get('setting_pkg','SYSTEM_MENU_TB_GET_ALL',$params);

        return $result;
    }

    /**
     * @param int $parent
     * @return array
     * get list of system_menus deepened on parent id
     */
    function getList($parent,$view,$main,$user,$MENU_C0DE = -1,$system_id = null){

        $current_user = $this->session->userdata('user_data');
        $memo_key = serialize(array(
            $parent, $view, $main, $user, $MENU_C0DE, $system_id,
            $this->session->userdata('db_instance'),
            $current_user->id ?? null
        ));
        if (array_key_exists($memo_key, $this->menuListMemo)) {
            return $this->menuListMemo[$memo_key];
        }

        // This procedure's parent=-1 branch retains every other filter and ORDER BY SORT.
        // NULL/empty parents and code filters have different Oracle semantics: query them directly.
        $canonical_parent = (is_int($parent) && $parent >= 0)
            || (is_string($parent) && preg_match('/\A(?:0|[1-9][0-9]*)\z/D', $parent));
        if ($canonical_parent && $user !== null && $user !== '' && $user !== false
            && ($MENU_C0DE === -1 || $MENU_C0DE === '-1')) {
            $bulk_key = serialize(array(
                $view, $main, $user, $MENU_C0DE, $system_id,
                $this->session->userdata('db_instance'), $current_user->id ?? null
            ));
            if (!array_key_exists($bulk_key, $this->menuListBuckets)) {
                $rows = $this->getList(-1, $view, $main, $user, $MENU_C0DE, $system_id);
                if (is_array($rows)) {
                    $buckets = array();
                    foreach ($rows as $row) {
                        if (!is_array($row) || !array_key_exists('MENU_PARENT_NO', $row)
                            || ($row['MENU_PARENT_NO'] !== null && !is_int($row['MENU_PARENT_NO'])
                                && !(is_string($row['MENU_PARENT_NO'])
                                    && preg_match('/\A(?:0|-?[1-9][0-9]*)\z/D', $row['MENU_PARENT_NO'])))) {
                            $buckets = false;
                            break;
                        }
                        // A SQL NULL parent does not match any nonnegative parent predicate.
                        if ($row['MENU_PARENT_NO'] !== null) {
                            $buckets[(string) $row['MENU_PARENT_NO']][] = $row;
                        }
                    }
                    // Keep Oracle's returned order; equal SORT values have no defined tie-breaker.
                    $this->menuListBuckets[$bulk_key] = $buckets;
                }
            }
            if (isset($this->menuListBuckets[$bulk_key]) && is_array($this->menuListBuckets[$bulk_key])) {
                $bucket = $this->menuListBuckets[$bulk_key][(string) $parent] ?? array();
                if (is_array($bucket)) {
                    $this->menuListMemo[$memo_key] = $bucket;
                    return $bucket;
                }
            }
        }

        $params =array(
            array('name'=>':MENU_PARENT_NO_in','value'=>"{$parent}",'type'=>SQLT_CHR,'length'=>-1),
            array('name'=>':MAIN_MENU_IN','value'=>"{$main}",'type'=>SQLT_CHR,'length'=>-1),
            array('name'=>':VIEW_MENU_IN','value'=>"{$view}",'type'=>SQLT_CHR,'length'=>-1),

            array('name'=>':USER_NO_IN','value'=>"{$user}",'type'=>SQLT_CHR,'length'=>-1),
            array('name'=>':MENU_PARENT_CODE_IN','value'=>"{$MENU_C0DE}",'type'=>'','length'=>-1),
            array('name'=>':SYSTEM_ID','value'=>"{$system_id}",'type'=>'','length'=>-1),
            array('name'=>':REF_CUR_OUT','value'=>'cursor','type'=>OCI_B_CURSOR),
            array('name'=>':MSG_OUT','value'=>'MSG_OUT','type'=>SQLT_CHR,'length'=>500),
        );

        $result = $this->New_rmodel->general_get('setting_pkg','SYSTEM_MENU_TB_GET_LIST',$params);

        if (is_array($result)) {
            $this->menuListMemo[$memo_key] = $result;
        }

        return $result;
    }



    /**
     * @param int $id
     * @return mixed
     * return one account ..
     */
    function get($id = 0){

        $params =array(
            array('name'=>':MENU_NO_in','value'=>"{$id}",'type'=>SQLT_CHR,'length'=>-1),
            array('name'=>':REF_CUR_OUT','value'=>'cursor','type'=>OCI_B_CURSOR),
            array('name'=>':MSG_OUT','value'=>'MSG_OUT','type'=>SQLT_CHR,'length'=>-1),
        );

        $result = $this->New_rmodel->general_get('setting_pkg','SYSTEM_MENU_TB_GET',$params);

        return $result;
    }


    /**
     * @param $data
     *
     * create new account ..
     */
    function create($data){
        $params =array();
        $this->_extract_data($params,$data);
        $result = $this->conn->excuteProcedures('setting_pkg','SYSTEM_MENU_TB_INSERT',$params);
        $this->clearMenuListMemo();
        return $result['MSG_OUT'];
    }


    /**
     * @param $data
     *
     * update exists account ..
     *
     */
    function edit($data){
        $params =array();
        $this->_extract_data($params,$data);
        $result = $this->conn->excuteProcedures('setting_pkg','SYSTEM_MENU_TB_UPDATE',$params);
        $this->clearMenuListMemo();

        return $result['MSG_OUT'];
    }

    /**
     * @param $data
     *
     * update exists account ..
     *
     */
    function sort($id,$count){
        $params =array(
            array('name'=>':MENU_NO_IN','value'=>$id,'type'=>'','length'=>-1),
            array('name'=>':SORT_IN','value'=>$count,'type'=>'','length'=>-1),
            array('name'=>':MSG_OUT','value'=>'MSG_OUT','type'=>SQLT_CHR,'length'=>-1),
        );
        $result = $this->conn->excuteProcedures('setting_pkg','SYSTEM_MENU_TB_SORT_UPDATE',$params);
        $this->clearMenuListMemo();

        return $result['MSG_OUT'];
    }

    /**
     * @param $id
     * delete account ..
     */
    function delete($id){

        $params =array(
            array('name'=>':MENU_NO_in','value'=>$id,'type'=>'','length'=>-1),
            array('name'=>':MSG_OUT','value'=>'MSG_OUT','type'=>SQLT_CHR,'length'=>-1),
        );

        $result = $this->conn->excuteProcedures('setting_pkg','SYSTEM_MENU_TB_DELETE',$params);
        $this->clearMenuListMemo();

        return $result['MSG_OUT'];

    }


    function getAllSystems(){

        $params =array(

            array('name'=>':GET_ROW_LOG','value'=>'cursor','type'=>OCI_B_CURSOR),
            array('name'=>':MSG_OUT','value'=>'MSG_OUT','type'=>SQLT_CHR,'length'=>-1)
        );

        $result = $this->New_rmodel->general_get('setting_pkg','GFC_SYSTEM_GET_ALL',$params);

        return $result;
    }



}
