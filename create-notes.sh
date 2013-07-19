#/!bin/bash
(echo "{toc}"; cd $1 git tag | grep "^[0-9]\{1,\}\(\.[0-9]\{1,\}\)*$" | sort -t. -k 1,1rn -k 2,2rn -k 3,3rn -k 4,4rn | xargs git show --name-only --pretty="format:%h - %an, %ar : %s" ; ) |  sed 's/tag /h2. tag /g' | php app/console confluence:release-notes --username="$2" --password="$3" "https://confluence.voycer.com/rpc/xmlrpc" 6062416

