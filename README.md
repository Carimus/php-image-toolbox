# PHP Image Toolbox

A toolbox for image manipulation in PHP.

[View documentation here](https://php-image-toolbox-docs.now.sh/)

## Deploying Docs

[`now`](https://now.sh) is used to deploy the docs.

```
$ composer docs
$ cd docs/
$ now --team="carimus" \
    --token="<token for carimus-deploy-bot>" \
    --name="php-image-toolbox-docs" \
    --public
$ now --team="carimus" \
    --token="<token for carimus-deploy-bot>" \
    alias \
    "<alias-returned-from-previous-step>"
    "php-image-toolbox-docs"
$ cd ../
```