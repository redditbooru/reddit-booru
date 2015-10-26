module.exports = function(grunt) {
    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),
        uglify: {
            options: {
                banner: '/* <%= pkg.name %> <%= grunt.template.today("yyyy-mm-dd") %> */\n'
            },
            dist: {
                files: {
                    './static/js/<%= pkg.name %>.min.js': [ './static/js/<%= pkg.name %>.js' ]
                }
            }
        },
        sass: {
            dist: {
                files: {
                    './static/scss/styles.css': './static/scss/styles.scss',
                    './static/scss/mobile.css': './static/scss/mobile.scss'
                }
            }
        },
        browserify: {
            options: {
                transform: [
                    [ 'babelify', { 'stage': 0 }],
                    [ 'browserify-handlebars' ]
                ],
                require: [
                    './node_modules/underscore/underscore.js:underscore',
                    './node_modules/jquery/dist/jquery.js:jquery',
                    './node_modules/backbone/backbone.js:backbone'
                ]
            },
            dist: {
                src: [ './static/js/dev/App.js', './views/*.handlebars' ],
                dest: './static/js/<%= pkg.name %>.js'
            }
        },
        watch: {
            css: {
                files: [
                    './static/scss/*.scss'
                ],
                tasks: [ 'sass' ]
            },
            js: {
                files: [
                    './static/js/dev/**/*.js',
                    './views/**/*.handlebars'
                ],
                tasks: [ 'browserify' ],
            },
            configFiles: {
                files: [ 'Gruntfile.js' ],
                options: {
                    reload: true
                }
            }
        }
    });

    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-browserify');
    grunt.loadNpmTasks('grunt-sass');

    grunt.registerTask('default', ['browserify', 'sass', 'uglify']);

};