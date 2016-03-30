#考呀呀消息服务API接口

[toc]

##一、运行方法
1. 执行`composer install`
2. 复制`.env.example`到`.env`并修改其中的数据库配置。
3. 运行`php artisan migrate`执行数据库迁移。
4. 运行`php -S 0.0.0.0:8080 -t public` 开启服务（测试用）。

##二、单元测试

```
phpunit
```

##三、接口文档

### 3.1 用户组

#### 1) 获取用户组列表:

请求：`GET /groups`
响应：

```
{
  "data": [
    {
      "id": 1,
      "name": "major group",
      "created_at": "2016-03-30 06:28:17",
      "updated_at": "2016-03-30 06:28:17"
    },
    {
      "id": 2,
      "name": "second group",
      "created_at": "2016-03-30 06:29:39",
      "updated_at": "2016-03-30 06:29:39"
    }
  ]
}
```

#### 2) 添加用户组：

请求：`POST /groups`

参数：

1. name(string): 用户组名称

请求示例

```
curl -H "Content-Type: application/json" --data '{"name": "major group"}' $host/groups
```

响应

```
{
  "data": {
    "name": "major group",
    "updated_at": "2016-03-30 06:28:17",
    "created_at": "2016-03-30 06:28:17",
    "id": 1
  }
}
```

#### 3) 添加用户到用户组：

请求： `POST /groups/add_users`

参数：

1. group_id(int): 用户组id
1. users(array of int): 要添加入的用户列表

```
{
	group_id: 1,
	users: [1, 2, 3]
}
```

请求示例

```
curl -H "Content-Type: application/json" --data '{"group_id": 1, "users": [1, 2, 3]}' $host/groups/add_users
```

#### 4) 从用户组删除用户：

请求：`DELETE /groups/delete_users`

参数：

1. group_id(int): 用户组
1. users(array of int): 要删除的用户

```
{
	group_id: 1,
	users: [1, 2, 3]
}
```

请求示例

```
curl -H "Content-Type: application/json" -X DELETE --data '{"group_id": 1, "users": [1, 2, 3]}' $host/groups/delete_users
```

#### 5) 删除用户组

请求：`DELETE /groups/{group_id}`

参数：

1. group_id(int) 用户组ID

请求示例

```
curl -X DELETE $host/groups/2
```

### 3.2 消息

#### 1) 发送消息

请求：`POST /messages/`

参数：

1. content(string): 消息内容
2. target_type(string): 消息发送类型，可选项为：`user`、`group`、`globale`，分别为发送给用户的消息，发送给群组的消息和发送给全体的消息。
3. targets(array of int): 发送类型为`user`和`group`时可用，标示要接收的群体。
4. sender_id(int): 消息发送者ID

example:

```
{
	'content': 'this is a message',
	'target_type': 'user',
	'targets': [1, 2, 3],
	'sender_id': 1
}
```

请求示例

```
curl -H "Content-Type: application/json" --data '{"content":"this is a message","target_type":"user","targets":[1,2,3],"sender_id":1}' $host/messages
```

响应：

```
{"data":[]}
```

#### 2) 获取未读消息数量

请求：`GET /users/{user_id}/unread_messages_count`

参数:

1. user_id(int)：用户ID

请求示例

```
curl $host/users/1/unread_messages_count
```

响应

```
{"data":1}
```

#### 3) 获取未读消息

请求：`GET /users/{user_id}/unread_messages`

参数:

1. user_id(int)：用户ID

请求示例

```
curl $host/users/1/unread_messages
```

响应：

```
{
  "data": [
    {
      "id": 1,
      "content": "this is a message",
      "created_at": "2016-03-30 06:42:59",
      "sender_id": 1
    }
  ]
}
```

#### 4) 阅读消息

请求：`POST /messages/read`

参数：

1. user_id(int)：用户ID
2. message_id(int): 消息ID

请求示例：

```
curl -H "Content-Type: application/json" --data '{"user_id": 1,"message_id":1}' $host/messages/read
```
#### 5) 获取已读消息

请求：`GET /users/{user_id}/read_messages`

参数:

1. user_id(int)：用户ID

请求示例

```
curl $host/users/1/unread_messages
```

响应：

```
{
  "data": [
    {
      "id": 1,
      "content": "this is a message",
      "created_at": "2016-03-30 06:42:59",
      "sender_id": 1
    }
  ]
}
```
