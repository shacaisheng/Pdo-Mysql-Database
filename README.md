一个支持链式操作的PDO数据库操作类

//实例
$pdo = DB::getInstance();
//查询全部
$pdo->table('xxx')->->select();
//查询一条
$pdo->table('xxx')->where('id',1,'<','AND')->find();
//查询字段
$pdo->table('xxx')->where('sex',1)->where('name','persi')->fields(array('id','name'))->order('id')->find();
//链接查询
$pdo->table('xxx')->join('user as us','us.id=xxx.uid')->where('us.id',1)->fields(array('id','name'))->find();
//更新
$pdo->table('xxx')->data(array('id'=>1,'name'=>'persi'))->where('id',1)->update();
//插入
$pdo->table('xxx')->data(array('id'=>1,'name'=>'persi'))->where('id',1)->insert();
//删除
$pdo->table('xxx')->where('id',1)->delete();

更新和删除都做了安全处理，无where条件禁止操作。
如果需要删除全部
$pdo->table('xxx')->where(1,1)->update();
