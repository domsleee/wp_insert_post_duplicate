# WordPress `wp_insert_post` concurrent issue demonstration

A demonstration of how posts with the same `post_name` can occur

## Usage

```bash
docker-compose up -d
# Wait for WordPress to load
docker-compose exec wordpress ./script.sh
```
