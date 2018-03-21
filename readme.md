Introduction
----------
MARVEL has developed an API providing details about the MARVEL Universe. See details and register at https://developer.marvel.com/.
This project queries MARAVEL's API using Artisan, Laravel's built-in comman line interface.

Prerequisites
-------------
Create a MARVEL developer account to obtain the public and private key. Add them into your environment (.env file) with variable names MARVEL_PRIVATE_KEY and MARVEL_PUBLIC_KEY.

Usage
-----
There is only one custom command availabe, with the format

marvel character data [path]

- character - is the name of the character for which details should be got.
- data - type of the data to be obtained, can be Event, Story, Comic or Series.
- path (optional) - path to file where data should be saved. Supported extensions are xlsx, xlsm, xltx, xltm, xl  
  s, xlt, ods, ots, slk, xml, gnumeric, htm, html, csv, txt, pdf.

When the path is not specified a file in the working directory is created, whose name is a composition of the character and data type.

DISCLAIMER
----------
The generated output does not include any copyright notice. This tool demonstrates how to get data from the MARVEL API, it is inappropriate for generating documents for any kind of usage. The user of this tool is responsible for including all relevant copyrights and trademarks into the generated files.
