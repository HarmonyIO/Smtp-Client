#!/usr/bin/env bash
openssl genrsa -out harmony.io.key 2048

openssl req -new -key harmony.io.key -out harmony.io.csr -subj "//C=NL\ST=Netherlands\O=harmonyio\CN=harmony.io"
openssl x509 -req -days 365 -in harmony.io.csr -signkey harmony.io.key -out harmony.io.crt
cat harmony.io.crt harmony.io.key > harmony.io.pem
rm harmony.io.csr
