一个支持链式操作的PDO数据库操作类
全程采用预编译与参数绑定，帮你告别SQL注入。

//实例<br />
$pdo = DB::getInstance();<br />
//查询全部<br />
$pdo->table('xxx')->->select();<br />
//查询一条<br />
$pdo->table('xxx')->where('id',1,'<','AND')->find();<br />
//查询字段<br />
$pdo->table('xxx')->where('sex',1)->where('name','persi')->fields(array('id','name'))->order('id')->find();<br />
//链接查询<br />
$pdo->table('xxx')->join('user as us','us.id=xxx.uid')->where('us.id',1)->fields(array('id','name'))->find();<br />
//更新<br />
$pdo->table('xxx')->data(array('id'=>1,'name'=>'persi'))->where('id',1)->update();<br />
//插入<br />
$pdo->table('xxx')->data(array('id'=>1,'name'=>'persi'))->where('id',1)->insert();<br />
//删除<br />
$pdo->table('xxx')->where('id',1)->delete();<br />

更新和删除都做了安全处理，无where条件禁止操作。<br />
如果需要删除全部<br />
$pdo->table('xxx')->where(1,1)->update();<br />

正在完善。。。。
如果您有好的想法，或者有发现什么BUG，建议，欢迎联系我  persi@sixsir.com
