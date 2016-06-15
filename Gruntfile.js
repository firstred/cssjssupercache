module.exports = function(grunt) {

    grunt.initConfig({
        compress: {
            main: {
                options: {
                    archive: 'cssjssupercache.zip'
                },
                files: [
                    {src: ['controllers/**'], dest: 'cssjssupercache/', filter: 'isFile'},
                    {src: ['classes/**'], dest: 'cssjssupercache/', filter: 'isFile'},
                    {src: ['docs/**'], dest: 'cssjssupercache/', filter: 'isFile'},
                    {src: ['override/**'], dest: 'cssjssupercache/', filter: 'isFile'},
                    {src: ['translations/**'], dest: 'cssjssupercache/', filter: 'isFile'},
                    {src: ['upgrade/**'], dest: 'cssjssupercache/', filter: 'isFile'},
                    {src: ['views/**'], dest: 'cssjssupercache/', filter: 'isFile'},
                    {src: 'config.xml', dest: 'cssjssupercache/'},
                    {src: 'index.php', dest: 'cssjssupercache/'},
                    {src: 'cssjssupercache.php', dest: 'cssjssupercache/'},
                    {src: 'logo.png', dest: 'cssjssupercache/'},
                    {src: 'logo.gif', dest: 'cssjssupercache/'}
                ]
            }
        }
    });

    grunt.loadNpmTasks('grunt-contrib-compress');

    grunt.registerTask('default', ['compress']);
};