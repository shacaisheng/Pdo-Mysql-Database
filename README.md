一个支持链式操作的PDO数据库操作类

//实例\n
$pdo = DB::getInstance();\n
//查询全部\n
$pdo->table('xxx')->->select();\n
//查询一条\n
$pdo->table('xxx')->where('id',1,'<','AND')->find();\n
//查询字段\n
$pdo->table('xxx')->where('sex',1)->where('name','persi')->fields(array('id','name'))->order('id')->find();\n
//链接查询\n
$pdo->table('xxx')->join('user as us','us.id=xxx.uid')->where('us.id',1)->fields(array('id','name'))->find();\n
//更新\n
$pdo->table('xxx')->data(array('id'=>1,'name'=>'persi'))->where('id',1)->update();\n
//插入\n
$pdo->table('xxx')->data(array('id'=>1,'name'=>'persi'))->where('id',1)->insert();\n
//删除\n
$pdo->table('xxx')->where('id',1)->delete();\n

更新和删除都做了安全处理，无where条件禁止操作。\n
如果需要删除全部\n
$pdo->table('xxx')->where(1,1)->update();\n
