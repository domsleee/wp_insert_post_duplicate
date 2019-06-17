# WordPress `wp_insert_post` concurrent issue demonstration

A demonstration of how `wp_insert_post` can result in posts with the same `post_name`

## Usage

First, we must build the docker image for the `wordpress` container. These are based on the wordpress images in docker hub, but differ because we need to compile php with an additional flag for `semaphores`:
```bash
./.docker/apache/build.sh;
./.docker/wordpress/build.sh;
./.docker/wordpress_dec/build.sh;
```

Start the docker containers, then wait for mysql to load (typically 10-15s):
```bash
docker-compose up -d;
```

Then, use the provided script to demonstrate the issue:
```bash
docker-compose exec wordpress ./script.sh;
```

## Expected result

This shows that post ids 4 and 5 have the same `post_name`:

```bash
+ wp --allow-root core install --url=http://localhost:8080/wordpress --title=Example --admin_user=supervisor --admin_password=strongpassword --admin_email=info@example.com
sh: 1: -t: not found
Success: WordPress installed successfully.
+ wp --allow-root plugin activate my_plugin
Plugin 'my_plugin' activated.
Success: Activated 1 of 1 plugins.
+ wp --allow-root my_plugin
PARENT inserted as 4: test_post
CHILD inserted as 5: test_post
```

