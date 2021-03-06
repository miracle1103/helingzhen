<?php
/**
 * Created by PhpStorm.
 * 下注
 * User: yike
 * Date: 2016/6/6
 * Time: 10:57
 */
if (!defined('IN_IA')) {
    exit('Access Denied');
}
global $_W, $_GPC;
$callback = $_GPC['callback'];
$user1 = pdo_fetch('select * from '.tablename('yike_members').' where uid = :uid', array(':uid' => $_W['member']['uid']));
$guess = pdo_fetch('select * from '.tablename('yike_guess_guess').' where id = :id', array(':id' => $_GPC['guess_id']));
if(time() > $guess['end_time']){
    show_jsonp(0,array('result' => '已超过投注时间'),$callback);
}
$user = pdo_fetch('select * from '.tablename('mc_members').' where uid = :uid', array(':uid' => $_W['member']['uid']));
if($user['credit1'] - $_GPC['money'] < 0){
    show_jsonp(0,array('result' => '余额不足，下注失败'),$callback);
}
$data = array(
    'user_id' => $user['uid'],
    'uniacid' => $_W['uniacid'],
    'guess_id' => $_GPC['guess_id'],
    'bet' => $_GPC['bet'],
    'money' => $_GPC['money'],
    'buy_time' => time()
);
$result = pdo_insert('yike_guess_order', $data);

$data4 = array(
    'add_money' => $user1['add_money'] - $_GPC['money']
);
pdo_update('yike_members',$data4,array(
    'uid' => $user1['uid']
));

$data1 = array(
    'credit1' => $user['credit1'] - $_GPC['money']
);
pdo_update('mc_members', $data1, array(
    'uid' => $user['uid']
));
$data2 = array(
    'uid' => $user['uid'],
    'uniacid' => $_W['uniacid'],
    'money' => $_GPC['money'],
    'type' => 2,
    'balance' => $user['credit1'] - $_GPC['money'],
    'create_time' => time(),
    'name' => '下注'
);
pdo_insert('yike_guess_balance', $data2);
$setdata = pdo_fetch("select * from " . tablename('yike_guess_sysset') . ' where uniacid=:uniacid limit 1', array(
    ':uniacid' => $_W['uniacid']
));
$set = unserialize($setdata['sets']);
if($set['task']['integral']['ones']){
    $ok_task = unserialize($user1['ok_task']);
    if($user1['bet_time'] < strtotime(date('Y-m-d', time()))){
        $data = array(
            'credit1' => $user['credit1'] - $_GPC['money'] + $set['task']['integral']['ones']
        );
        pdo_update('mc_members', $data, array(
            'uid' => $_W['member']['uid']
        ));
        $data2 = array(
            'uid' => $_W['member']['uid'],
            'uniacid' => $_W['uniacid'],
            'money' => $set['task']['integral']['ones'],
            'type' => 1,
            'balance' => $user['credit1'] - $_GPC['money'] + $set['task']['integral']['ones'],
            'create_time' => time(),
            'name' => '奖励'
        );
        pdo_insert('yike_guess_balance', $data2);
        $ok_task['task']['ones'] = 1;
    }
    $data3 = array(
        'bet_time' => time(),
        'ok_task' => serialize($ok_task),
    );
    pdo_update('yike_members', $data3, array(
        'uid' =>  $_W['member']['uid']
    ));
}else{
    $data3 = array(
        'bet_time' => time()
    );
    pdo_update('yike_members', $data3, array(
        'uid' =>  $_W['member']['uid']
    ));
}
if(isset($set['notice']['new'])){
    $result1 = m('notice')->sendTplNotice($_W['openid'], $set['notice']['new'], array('result' => array('value' => '出票成功，祝您中奖'), 'issueInfo' => array('value' => $guess['name']), 'betTime' => array('value' => date('Y年m月d日 H:i',time())), 'fee' => array('value' => $_GPC['money'].'积分'), 'drawTime' => array('value' => '比赛结束后')));
}
if($result){
    show_jsonp(1,array('result' => '下注成功'),$callback);
}else{
    show_jsonp(0,array('result' => '下注失败'),$callback);
}