module.exports = function(grunt) {
    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),
        concat: {
            options: {
                separator: ';'
            },
            dist: {
                src: [
                    'static/js/dev/lib/jquery.min.js',
                    'static/js/dev/lib/underscore.min.js',
                    'static/js/dev/lib/backbone.min.js',
                    'static/js/dev/lib/handlebars.runtime.js',
                    'static/js/dev/model/*.js',
                    'static/js/templates.js',
                    'static/js/dev/view/*.js',
                    'static/js/dev/App.js'
                ],
                dest: 'static/js/<%= pkg.name %>.js'
            }
        },
        uglify: {
            options: {
                banner: '/* <%= pkg.name %> <%= grunt.template.today("yyyy-mm-dd") %> */\n'
            },
            dist: {
                files: {
                    'static/js/<%= pkg.name %>.min.js': ['<%= concat.dist.dest %>']
                }
            }
        },
        sass: {
            dist: {
                files: {
                    'static/scss/styles.css': 'static/scss/styles.scss'
                }
            }
        },
        handlebars: {
            compile: {
                options: {
                    namespace: 'RB.Templates',
                    wrapped:true,
                    processName: function(filename) {
                        filename = filename.split('/');
                        filename = filename[filename.length - 1];
                        return filename.split('.')[0];
                    }
                },
                files: {
                    'static/js/templates.js': 'views/*.handlebars'
                }
            }
        },
        watch: {
            files: [
                'static/js/dev/lib/*.js',
                'static/js/dev/model/*.js',
                'static/js/dev/view/*.js',
                'static/js/dev/*.js',
                'views/*.handlebars',
                'views/partials/*.handlebars',
                'static/scss/*.scss'
            ],
            tasks: ['handlebars', 'concat', 'sass']
        }
    });

    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-contrib-concat');
    grunt.loadNpmTasks('grunt-contrib-handlebars');
    grunt.loadNpmTasks('grunt-sass');

    grunt.registerTask('default', ['handlebars', 'sass', 'concat', 'uglify']);

};