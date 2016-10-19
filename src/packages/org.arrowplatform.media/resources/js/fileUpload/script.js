window.mydropbox = function () {

    var dropboxSet = $('.org-arrowplatform-media-dropbox');

    dropboxSet.each(function (index, el) {
        var dropbox = $(el);
        if (dropbox.attr("in"))
            return;
        dropbox.attr("in", 1);

        message = $('.message', dropbox);
        dropbox.filedrop({
            // The name of the $_FILES entry:
            paramname: 'file',

            maxfiles: 5,
            maxfilesize: 2, // in mb
            url: './media,-op,-/operations/addFileToObject?model=' + dropbox.attr('model') + "&key=" + dropbox.attr('key') + "&name=" + dropbox.attr('name'),

            uploadFinished: function (i, file, response) {
                $.data(file).addClass('done');
                // response is the JSON object that post_file.php returns
            },

            error: function (err, file) {
                switch (err) {
                    case 'BrowserNotSupported':
                        showMessage('Your browser does not support HTML5 file uploads!');
                        break;
                    case 'TooManyFiles':
                        alert('Too many files! Please select 5 at most!');
                        break;
                    case 'FileTooLarge':
                        alert(file.name + ' is too large! Please upload files up to 2mb.');
                        break;
                    default:
                        break;
                }
            },

            // Called before each upload is started
            beforeEach: function (file) {
                return true;
                if (!file.type.match(/^image\//)) {
                    alert('Only images are allowed!');

                    // Returning false will cause the
                    // file to be rejected
                    return false;
                }
            },

            uploadStarted: function (i, file, len) {
                createImage(file);
            },

            progressUpdated: function (i, file, progress) {
                $.data(file).find('.progress').width(progress);
            },
            afterAll: function () {

                var managebox = $(".org-arrowplatform-media-managebox");
                if (managebox.length > 0) {
                    managebox.each(function (index, el) {
                        var e = $(el);
                        e.load("./media,-/plugins/manageObjectFiles?model=" + e.attr("model") + "&key=" + e.attr("key") + "&name=" + e.attr("name") + " .org-arrowplatform-media-managebox-container", function () {
                                //$.getScript("./arrowplatform/src/packages/org.arrowplatform.media/resources/js/fileUpload/script.js");
                                window.mydropbox();
                            }
                        )
                    });
                }

            }

        });

        var template = '<div class="preview">' +
            '<span class="imageHolder">' +
            '<img />' +
            '<span class="uploaded"></span>' +
            '</span>' +
            '<div class="progressHolder">' +
            '<div class="progress"></div>' +
            '</div>' +
            '</div>';

        function createImage(file) {

            var preview = $(template),
                image = $('img', preview);

            var reader = new FileReader();

            image.width = 100;
            image.height = 100;

            reader.onload = function (e) {

                // e.target.result holds the DataURL which
                // can be used as a source of the image:

                if (file.type.match(/^image\//)) {
                    image.attr('src', e.target.result);
                } else {
                    image.attr('src', "./resources/js/fileUpload/img/file-icon.png");
                }
            };

            // Reading the file as a DataURL. When finished,
            // this will trigger the onload function above:
            reader.readAsDataURL(file);

            message.hide();
            preview.appendTo(dropbox);

            // Associating a preview container
            // with the file, using jQuery's $.data():

            $.data(file, preview);
        }

        function showMessage(msg) {
            message.html(msg);
        }

    });

}

window.mydropbox();