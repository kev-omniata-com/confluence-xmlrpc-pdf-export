
confluence-xmlrpc-pdf-export
============================

Use this project to backup a space from your confluence instance.

You have to [enable the remote api of Confluence](https://confluence.atlassian.com/display/DOC/Enabling+the+Remote+API) to use this application. By default the api is not enabled.

Start the console application
````app/console confluence:backup --username=your-username --password=your-password "http://your-confluence-url/rpc/xmlrpc" SPACENAME "/tmp/export.pdf"````. Leave the password empty to enable interactive hidden input mode.
