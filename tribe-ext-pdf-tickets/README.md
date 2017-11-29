The [mPDF library](https://github.com/mpdf/mpdf) we rely upon for this extension uses Composer and autoloading (as of their version 7 from October 2017):
> Composer is now the only officially supported installation method. There are no pre-packaged library archives.

View mPDF's changelogs at [https://github.com/mpdf/mpdf/releases](https://github.com/mpdf/mpdf/releases)

### Instructions for this extension's build and package process:

1. Clone this GitHub repository and create a new branch for your changes.
1. [Install Composer](https://getcomposer.org/download/) into your branch's directory.
    1. You may want to install Composer at `/Users/YOUR-NAME/` and then make a *symlink* at **`/Users/YOUR-NAME/git/PROJECT-NAME/composer.phar`** pointing to it at `/Users/YOUR-NAME/composer.phar` so that you can have Composer installed just once on your machine but run it in each project you use Composer for.
    1. Note that the above tip is for Mac (*nix) based and file paths and symlink-type functionality on Windows would have different instructions but likely the same concept.
1. Using Terminal, `cd` to your project directory. Example: `cd /Users/YOUR-NAME/git/PROJECT-NAME`
1. Run **`php composer.phar install`**
    1. This will build the `vendor` subdirectory you need to actually run the extension on your localhost. It will include the required autoload.php file, the mPDF library, and the libraries required by the autoloader and mPDF. *#Composer just did its magic!*
1. Make your code changes and test them on your localhost. Commit your changes to GitHub (and you would typically add the `Code Review` tag and go through that process).
1. **Once ready to build the finalized .zip to distribute to QA or to customers, run *`php composer.phar archive --format=zip --file tribe-ext-pdf-tickets`***
    1. Because we did not set a `--dir` argument for the `archive` command, Composer will create the .zip right in the project's directory. *#Convenient!*
1. Unzip this newly-created `tribe-ext-pdf-tickets.zip` file to make sure it got built correctly (excluding files like .gitignore, composer.json, etc).
1. Upload this `tribe-ext-pdf-tickets.zip` file to TheEventsCalendar.com's Media Library (or wherever you want to distribute it, such as uploading to Central for QA to test).
1. Delete this `tribe-ext-pdf-tickets.zip` file from your hard drive (or, at the very least, remove it from the project directory before you do any commits).

Like some of the other files you see in this GitHub repository, this README.md file does not get included in the final .zip that makes it to the customer. It and other files are excluded via Composer's build process, documented above.

### Known Issues ###

mPDF has these known issues, for which you might need to modify the Composer-generated mpdf directory's files **before distributing**:
* [https://github.com/mpdf/mpdf/pull/490/files](https://github.com/mpdf/mpdf/pull/490/files) must be manually performed **after** generating via Composer and **prior to zipping via Composer.** See that pull request's comments for additional context.
* Keep an eye on [https://github.com/mpdf/mpdf/issues/524](https://github.com/mpdf/mpdf/issues/524) so our extension's *get_mpdf()* method can be updated once that's fixed.