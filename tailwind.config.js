/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./src/**/*.{html,js,php}",
    "./node_modules/flowbite/**/*.js"
  ],
  theme: {
    extend: {
      fontFamily: {
        modak: ['Modak', 'cursive'],
        poppins: ['Poppins', 'sans-serif']
      },
      colors: {
        'teal': '#008080',
        'light-teal': '#20B2AA',
        'dark-teal': '#006666',
        'teal-blue': '#367588',
        'teal-green': '#128C7E',
      },
    },
  },
  plugins: [
    require('flowbite/plugin')
  ],
}