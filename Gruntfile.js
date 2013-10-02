module.exports = function(grunt) {
    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),
        concat: {
            options: {
                separator: ';'
            },
            dist: {
                src: [
                    'view/js/handlebars.runtime.js',
                    'view/js/templates.js',
                    'view/js/scripts.js'
                ],
                dest: 'view/js/<%= pkg.name %>.js'
            }
        },
        uglify: {
            options: {
                banner: '/* <%= pkg.name %> <%= grunt.template.today("yyyy-mm-dd") %> */\n'
            },
            dist: {
                files: {
                    'view/js/<%= pkg.name %>.min.js': ['<%= concat.dist.dest %>']
                }
            }
        },
        sass: {
            dist: {
                files: {
                    'view/css/default.css': 'view/css/default.scss',
                    'view/css/awwnime.css': 'view/css/awwnime.scss'
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
                    'view/js/templates.js': 'view/handlebars/*.handlebars'
                }
            }
        },
        watch: {
            files: [
                'view/js/scripts.js',
                'view/handlebars/*.handlebars',
                'view/css/*.scss'
            ],
            tasks: ['handlebars', 'concat', 'sass']
        }
    });

    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-jshint');
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-contrib-concat');
    grunt.loadNpmTasks('grunt-contrib-handlebars');
    grunt.loadNpmTasks('grunt-sass');

    grunt.registerTask('default', ['handlebars', 'sass', 'concat', 'uglify']);

};