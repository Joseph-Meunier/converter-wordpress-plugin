/**
 * Public JavaScript for WebP Converter plugin.
 */

(function() {
    'use strict';

    // refs object
    let refs = {};

    // Wait for DOM to be ready
    document.addEventListener('DOMContentLoaded', function() {
        refs.imagePreviews = document.querySelector('#webpConverterPreviews');
        refs.fileSelector = document.querySelector('.webp-converter-file-input');
        refs.downloadAllBtn = document.querySelector('#webpConverterDownloadAll');
        refs.formatSelect = document.querySelector('#webpConverterFormat');

        if (!refs.imagePreviews || !refs.fileSelector || !refs.downloadAllBtn || !refs.formatSelect) {
            return;
        }

        // Set up drag and drop
        setDragDrop(document.documentElement, processFiles);

        // File input change
        refs.fileSelector.addEventListener('change', fileSelectorChanged);

        // Download all button
        refs.downloadAllBtn.addEventListener('click', downloadAllImages);
    });

    function getFormatExt(format) {
        switch (format) {
            case 'jpeg': return 'jpg';
            case 'svg': return 'svg';
            case 'webp': return 'webp';
            case 'png': return 'png';
            case 'gif': return 'gif';
            default: return format;
        }
    }

    function getMimeType(format) {
        const map = {
            webp: 'image/webp',
            jpeg: 'image/jpeg',
            png: 'image/png',
            gif: 'image/gif',
            svg: 'image/svg+xml'
        };
        return map[format] || 'image/webp';
    }

    function getQuality(format) {
        if (typeof webpConverterSettings === 'undefined') {
            return 90;
        }
        switch (format) {
            case 'jpeg': return webpConverterSettings.jpegQuality || 90;
            case 'png': return webpConverterSettings.pngQuality || 100;
            case 'webp': return webpConverterSettings.webpQuality || 90;
            default: return 90;
        }
    }

    function getFormatName(format) {
        return format + '-images';
    }

    function addImageBox(container) {
        let imageBox = document.createElement('div');
        let progressBox = document.createElement('progress');
        imageBox.appendChild(progressBox);
        container.appendChild(imageBox);
        return imageBox;
    }

    function updateDownloadAllButton() {
        const hasImages = refs.imagePreviews && refs.imagePreviews.querySelectorAll('a[download]').length > 0;
        if (refs.downloadAllBtn) {
            refs.downloadAllBtn.style.display = hasImages ? 'inline-block' : 'none';
        }
    }

    async function downloadAllImages() {
        if (!refs.imagePreviews || !refs.formatSelect) return;

        const links = refs.imagePreviews.querySelectorAll('a[download]');
        if (links.length === 0) return;

        const zip = new JSZip();
        const format = refs.formatSelect.value;
        const folder = zip.folder(getFormatName(format));

        // Fetch all images and add to ZIP
        const promises = Array.from(links).map(async function(link) {
            const response = await fetch(link.href);
            const blob = await response.blob();
            const filename = link.getAttribute('download');
            folder.file(filename, blob);
        });

        await Promise.all(promises);

        // Generate ZIP and download
        const content = await zip.generateAsync({type: 'blob'});
        const url = URL.createObjectURL(content);
        const a = document.createElement('a');
        a.href = url;
        a.download = getFormatName(format) + '.zip';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }

    function processFile(file) {
        if (!file) {
            return;
        }

        if (!refs.imagePreviews) return;

        let imageBox = addImageBox(refs.imagePreviews);

        // Load the data into an image
        new Promise(function(resolve, reject) {
            let rawImage = new Image();

            rawImage.addEventListener('load', function() {
                resolve(rawImage);
            });

            rawImage.addEventListener('error', function() {
                reject(new Error('Failed to load image'));
            });

            rawImage.src = URL.createObjectURL(file);
        })
        .then(function(rawImage) {
            // Convert image to selected format via canvas blob
            return new Promise(function(resolve, reject) {
                let format = refs.formatSelect.value;
                let canvas = document.createElement('canvas');
                let ctx = canvas.getContext('2d');

                canvas.width = rawImage.width;
                canvas.height = rawImage.height;
                ctx.drawImage(rawImage, 0, 0);

                if (format === 'svg') {
                    // For SVG: create an SVG element with base64 embedded image
                    let svgDoc = document.implementation.createDocument('http://www.w3.org/2000/svg', 'svg', null);
                    let svgElement = svgDoc.documentElement;
                    svgElement.setAttribute('width', rawImage.width);
                    svgElement.setAttribute('height', rawImage.height);
                    svgElement.setAttribute('xmlns', 'http://www.w3.org/2000/svg');

                    // Get the image as base64 data URL from canvas
                    let dataURL = canvas.toDataURL('image/png');

                    let imageElement = svgDoc.createElementNS('http://www.w3.org/2000/svg', 'image');
                    imageElement.setAttribute('width', rawImage.width);
                    imageElement.setAttribute('height', rawImage.height);
                    imageElement.setAttribute('href', dataURL);
                    svgElement.appendChild(imageElement);

                    let serializer = new XMLSerializer();
                    let svgString = serializer.serializeToString(svgElement);
                    let svgBlob = new Blob([svgString], {type: 'image/svg+xml'});
                    resolve(URL.createObjectURL(svgBlob));
                } else {
                    let quality = getQuality(format) / 100;
                    canvas.toBlob(function(blob) {
                        resolve(URL.createObjectURL(blob));
                    }, getMimeType(format), quality);
                }
            });
        })
        .then(function(imageURL) {
            // Load image for display on the page
            return new Promise(function(resolve, reject) {
                let scaledImg = new Image();

                scaledImg.addEventListener('load', function() {
                    resolve({imageURL: imageURL, scaledImg: scaledImg});
                });

                scaledImg.addEventListener('error', function() {
                    reject(new Error('Failed to load converted image'));
                });

                scaledImg.setAttribute('src', imageURL);
            });
        })
        .then(function(data) {
            // Inject into the DOM
            let format = refs.formatSelect.value;
            let ext = getFormatExt(format);
            let imageLink = document.createElement('a');

            imageLink.setAttribute('href', data.imageURL);
            imageLink.setAttribute('download', file.name + '.' + ext);
            imageLink.appendChild(data.scaledImg);

            imageBox.innerHTML = '';
            imageBox.appendChild(imageLink);

            // Update Download All button visibility
            updateDownloadAllButton();
        })
        .catch(function(error) {
            console.error('Error processing file:', error);
            if (imageBox && imageBox.parentNode) {
                imageBox.parentNode.removeChild(imageBox);
            }
            updateDownloadAllButton();
        });
    }

    function processFiles(files) {
        for (let i = 0; i < files.length; i++) {
            processFile(files[i]);
        }
    }

    function fileSelectorChanged() {
        if (refs.fileSelector) {
            processFiles(refs.fileSelector.files);
            refs.fileSelector.value = '';
        }
    }

    // Drag and drop handlers
    function dragenter(e) {
        e.stopPropagation();
        e.preventDefault();
    }

    function dragover(e) {
        e.stopPropagation();
        e.preventDefault();
    }

    function drop(callback, e) {
        e.stopPropagation();
        e.preventDefault();
        callback(e.dataTransfer.files);
    }

    function setDragDrop(area, callback) {
        area.addEventListener('dragenter', dragenter, false);
        area.addEventListener('dragover', dragover, false);
        area.addEventListener('drop', function(e) {
            drop(callback, e);
        }, false);
    }
})();