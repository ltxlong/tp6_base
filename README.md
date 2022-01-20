基于ThinkPHP 6.0

===========================================================

分层：

controller

model

view

service

dao

在MVC的基础上扩展了service和dao层

service层：

服务层，或者称为逻辑层

写逻辑的地方，不和模型直接接触

dao层：

数据操作层，和模型直接接触

有了dao层，model层就不操作数据了

至于第三方扩展的服务类，放到common文件夹的service文件夹里


controller->service->dao->model

============================================================

关于类的实例化：

全部通过new static()静态方法实例化

即：

类名::obj()

obj()是静态方法，通过new static()实例化，一般不用单例

并且为了写法统一，私有化构造函数类防止new

也可以结合构造函数来传参，当然构造函数还是私有的


这样的实例化更加优雅，至于依赖注入，因为类一旦多了就不好

所以尽量不用依赖注入，就用这种方式

这种方式不用写多一层注释就可以被IDE识别跳转

dao、model、service、validate的文件只要继承了相应的base类（common/base目录里），

就可以通过类名::obj()类实例化

至于自定义异常类，不用new static()的方式，还是用new类实例化

至于工具类，全部方法都是静态的，就不用实例化了

==============================================================

controller文件有后缀：Controller
service文件有后缀：Service
dao文件有后缀：Dao
model文件没有后缀！（一定不能有，会报错，写法也不好写）
trait文件有后缀：Trait
middleware文件有后缀：Middleware
job文件有后缀：Job
exception文件有后缀：Excepton
validate文件有后缀：Validate
listener文件有后缀：Listener





