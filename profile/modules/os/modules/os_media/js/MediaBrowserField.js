(function () {

  angular.module('MediaBrowserField', ['mediaBrowser', 'FileEditorModal', 'EntityService', 'ui.sortable', 'DrupalSettings', 'UrlGenerator'])
    .config(['$injector', function ($injector) {
      try {
        depManager = $injector.get('DependenciesProvider');
        depManager.AddDependency('formElement', 'mediaBrowserField');
      }
      catch (err) {
      }
    }])
    .directive('mediaBrowserField', ['mbModal', 'EntityService', 'drupalSettings', 'urlGenerator', function (mbModal, EntityService, settings, url) {

      function link(scope, elem, attr, ngModelController) {
        // everything to define
        var service = new EntityService('media', 'mid');
        scope.showHelp = false;
        if (scope.$parent.element && scope.$parent.element.custom_directive_parameters.hide_helpicon) {
          scope.hideHelpicon = scope.$parent.element.custom_directive_parameters.hide_helpicon;
        } else {
          scope.hideHelpicon = false;
        }
        if (attr['panes']) {
          scope.panes = attr['panes'].split(',');
        }
        else {
          scope.panes = ['upload', 'web', 'library'];
        }
        scope.required = attr['required'] == "";

        var types = {};
        scope.allowedTypes = scope.types.split(',');
        scope.extensionsFull = [];
        var extensionMap = settings.fetchSetting('extensionMap');
        for (var i = 0; i < scope.allowedTypes.length; i++) {
          var type = scope.allowedTypes[i];
          types[type] = type;
          if (extensionMap[type] && extensionMap[type].length) {
            scope.extensionsFull = scope.extensionsFull.concat(extensionMap[type]);
          }
        }
        scope.extensionsFull.sort();

        scope.sortableOptions = {
          axis: 'y',
          handle: '.tabledrag-handle'
        };

        var generateFunc = function (i) {
          return function(file) {
            scope.selectedFiles[i] = angular.copy(file);
            return file;
          }
        };

        if (!store.isNew()) {
          scope.selectedFiles = store.fetchData(scope.fieldName);
        }
        else if (!Array.isArray(scope.selectedFiles)) {
          scope.selectedFiles = [];
        }


        if (Array.isArray(scope.$parent.value)) {
          for (i = 0; i < scope.$parent.value.length; i++) {
            service.fetchOne(scope.$parent.value[i]).then(generateFunc(i));
          }
        }
        else if (scope.$parent.value) {
          service.fetchOne(scope.$parent.value).then(generateFunc(0));
        }

        if (scope.selectedFiles.length === 0 && scope.files !== "") {
          let fids = scope.files.split(',');
          for (let i = 0; i < fids.length; i++) {
            let fid = fids[i];
            service.fetchOne(fid).then(generateFunc(i));
          }
          store.setData(scope.fieldName, scope.selectedFiles);
        }

        // prefetch the files now so user can open Media Browser later
        service.fetch();

        scope.$on('EntityService.media.update', function (e, file) {
          for (var i = 0; i<scope.selectedFiles.length; i++) {
            if (file.mid == scope.selectedFiles[i].mid) {
              scope.selectedFiles[i] = angular.copy(file);
            }
          }
          store.setData(scope.fieldName, scope.selectedFiles);
        });

        scope.sendToBrowser = function($files) {
          var params = {
            files: $files,
            onSelect: scope.addFile,
            types: types
          };
          mbModal.open(params);
          for (var i = 0; i < scope.selectedFiles; i++) {
            highlightDupe(scope.selectedFiles[i], false);
          }
        };

        scope.addFile = function ($files) {
          for (var i = 0; i < $files.length; i++) {
            var found = false;
            for (var j = 0; j < scope.selectedFiles.length; j++) {
              highlightDupe(scope.selectedFiles[j], false);
              if ($files[i].mid === scope.selectedFiles[j].mid) {
                scope.selectedFiles[j] = angular.copy($files[i]);
                highlightDupe(scope.selectedFiles[j], true);
                found = true;
                break;
              }
            }
            if (!found) {
              scope.selectedFiles.push($files[i]);
              if (scope.cardinality == 1) {
                scope.$parent.value = $files[i].id;
              }
              else {
                scope.$parent.value = scope.$parent.value || [];
                scope.$parent.value.push($files[i].id);
              }
            }
          }
          store.setData(scope.fieldName, scope.selectedFiles);
          if (ngModelController) {
            ngModelController.$setDirty();
            ngModelController.$setTouched();
          }
        };

        scope.removeFile = function ($index) {
          scope.selectedFiles.splice($index, 1);
          if (scope.cardinality == 1) {
            scope.$parent.value = 0;
          }
          else {
            scope.$parent.value.splice($index, 1);
          }
          store.setData(scope.fieldName, scope.selectedFiles);
          if (ngModelController) {
            ngModelController.$setDirty();
            ngModelController.$setTouched();
          }
        };

        scope.replaceFile = function ($inserted, $index) {
          scope.selectedFiles.splice($index, 1, $inserted[0]);
          if (scope.cardinality == 1) {
            scope.$parent.value = $inserted[0].id;
          }
          else {
            scope.$parent.value.splice($index, 1, $inserted[0]);
          }
          store.setData(scope.fieldName, scope.selectedFiles);
          if (ngModelController) {
            ngModelController.$setDirty();
            ngModelController.$setTouched();
          }
        };

        function highlightDupe(file, toHighlight) {
          file.highlight = toHighlight;
        }

        scope.fieldIsFull = function () {
          if (scope.cardinality == -1) {
            return false;
          }

          return scope.selectedFiles.length >= scope.cardinality;
        };

        var label = elem.parent().find(' label');
        elem.parent().find(' > *').not(elem).remove();
        elem.before(label);
      }

      if (mbModal.requirementsMet()) {
        return {
          link: link,
          require: '?ngModel',
          templateUrl: function () {
            return url.generate(settings.fetchSetting('paths.mediaBrowser') + 'field.html?vers=' + settings.fetchSetting('version.mediaBrowser', false));
          },
          scope: {
            fieldId: '@',
            fieldName: '@',
            files: '@',
            maxFilesize: '@maxFilesize',
            types: '@',
            extensions: '@',
            upload_text: '@uploadText',
            droppable_text: '@droppableText',
            cardinality: '@',
            title: '@title',
            required_class: '@requiredClass'
          }
        }
      }
      else {
        // remove this element. It won't work right anyway
        return {
          link: function (elem, attr) {
            elem.remove();
          }
        }
      }
    }])
    .run(function () {
      angular.element(window).on('dragover drop', function(e) {
        e = e || event;
        e.preventDefault();
      });
    });

  var store;
  (function () {
    var form_id,
      new_form,
      data = {},
      inited = false;
    store = {
      init: function () {
        if (inited) {
          return;
        }

        inited = true;
        var old_id = sessionStorage['last_form'];
        form_id_input = document.querySelector('form input[name="form_build_id"]'),
        form_id = form_id_input != null ? form_id_input.value: "";

        if (!form_id) {
          new_form = true;
          return;
        }

        if (form_id != old_id) {
          delete sessionStorage[old_id];
          new_form = true;
          sessionStorage['last_form'] = form_id;
        }
        else {
          data = JSON.parse(sessionStorage[form_id]);
          new_form = false;
        }
      },
      fetchData: function (fieldName) {
        this.init();
        return data[fieldName];
      },
      setData: function (fieldName, newData) {
        this.init();
        data[fieldName] = newData;
        sessionStorage[form_id] = JSON.stringify(data);
      },
      isNew: function () {
        this.init();
        return new_form;
      }
    };
  })();
})();

// Written for new paragraph content inserted into the dom via Drupal Ajax so that Angular modules can be invoked on it.
(function ($, Drupal) {
  "use strict";

  Drupal.behaviors.osMediaAjaxContent = {
    attach: function (context, settings) {

      var parentSelector = $('.ajax-new-content').parents('.js-form-wrapper');
      $('.ajax-new-content', context).each(function () {
        var content = $(parentSelector);
        angular.element(context).injector().invoke(function($compile) {
          var scope = angular.element(content).scope();
          $compile(content)(scope);
        });
      });


    }
  };

})(jQuery, Drupal);
