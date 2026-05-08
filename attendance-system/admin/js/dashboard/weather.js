/**
 * Weather Forecast Module
 * Handles weather data fetching and display from Open-Meteo API
 */

// Default location (Butuan City, Philippines)
const DEFAULT_LATITUDE = 8.95;
const DEFAULT_LONGITUDE = 125.53;

/**
 * Get weather icon SVG based on weather code
 * @param {number} weatherCode - WMO Weather Interpretation Code
 */
function getWeatherIcon(weatherCode) {
    const iconContainer = document.getElementById('weather-icon');
    if (!iconContainer) return;
    
    iconContainer.innerHTML = '';
    
    let svgContent = '';
    
    if (weatherCode === 0) {
        svgContent = `<svg class="w-6 h-6 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>`;
    } else if (weatherCode >= 1 && weatherCode <= 3) {
        svgContent = `<svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"></path></svg>`;
    } else if (weatherCode >= 45 && weatherCode <= 48) {
        svgContent = `<svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"></path></svg>`;
    } else if ((weatherCode >= 51 && weatherCode <= 67) || (weatherCode >= 80 && weatherCode <= 82)) {
        svgContent = `<svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 18v-2m4 2v-4m4 4v-3"></path></svg>`;
    } else if ((weatherCode >= 71 && weatherCode <= 77) || (weatherCode >= 85 && weatherCode <= 86)) {
        svgContent = `<svg class="w-6 h-6 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M15 18h.01M9 18h.01M9 15h.01M12 15h.01M15 15h.01"></path></svg>`;
    } else if (weatherCode >= 95 && weatherCode <= 99) {
        svgContent = `<svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>`;
    } else {
        svgContent = `<svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"></path></svg>`;
    }
    
    iconContainer.innerHTML = svgContent;
}

/**
 * Get weather condition text based on weather code
 * @param {number} weatherCode - WMO Weather Interpretation Code
 * @returns {string} Weather condition description
 */
function getWeatherCondition(weatherCode) {
    if (weatherCode === 0) return 'Clear Sky';
    if (weatherCode >= 1 && weatherCode <= 3) return 'Partly Cloudy';
    if (weatherCode >= 45 && weatherCode <= 48) return 'Foggy';
    if (weatherCode >= 51 && weatherCode <= 55) return 'Drizzle';
    if (weatherCode >= 56 && weatherCode <= 57) return 'Freezing Drizzle';
    if (weatherCode >= 61 && weatherCode <= 67) return 'Rainy';
    if (weatherCode >= 71 && weatherCode <= 77) return 'Snowy';
    if (weatherCode >= 80 && weatherCode <= 82) return 'Rain Showers';
    if (weatherCode >= 85 && weatherCode <= 86) return 'Snow Showers';
    if (weatherCode >= 95 && weatherCode <= 99) return 'Thunderstorm';
    return 'Cloudy';
}

/**
 * Fetch weather data from Open-Meteo API
 * @param {number} latitude - Latitude coordinate
 * @param {number} longitude - Longitude coordinate
 */
export async function fetchWeatherData(latitude, longitude) {
    try {
        const apiUrl = `https://api.open-meteo.com/v1/forecast?latitude=${latitude}&longitude=${longitude}&current=temperature_2m,weather_code,relative_humidity_2m,wind_speed_10m&timezone=auto`;
        
        const response = await fetch(apiUrl);
        if (!response.ok) {
            throw new Error('Weather API request failed');
        }
        
        const data = await response.json();
        
        if (data.current) {
            const current = data.current;
            const weatherCode = current.weather_code;
            const temperature = current.temperature_2m;
            const humidity = current.relative_humidity_2m;
            const windSpeed = current.wind_speed_10m;
            
            getWeatherIcon(weatherCode);
            
            const conditionEl = document.getElementById('weather-condition');
            const temperatureEl = document.getElementById('weather-temperature');
            const detailsEl = document.getElementById('weather-details');
            
            if (conditionEl) {
                conditionEl.textContent = getWeatherCondition(weatherCode);
            }
            
            if (temperatureEl) {
                temperatureEl.textContent = `${temperature}°C`;
            }
            
            if (detailsEl) {
                detailsEl.textContent = `Humidity: ${humidity}% | Wind: ${windSpeed} km/h`;
            }
        }
    } catch (error) {
        console.error('Error fetching weather data:', error);
        const conditionEl = document.getElementById('weather-condition');
        const temperatureEl = document.getElementById('weather-temperature');
        const detailsEl = document.getElementById('weather-details');
        
        if (conditionEl) conditionEl.textContent = 'Unable to load weather';
        if (temperatureEl) temperatureEl.textContent = '-';
        if (detailsEl) detailsEl.textContent = 'Please try again later';
    }
}

/**
 * Initialize weather data fetching
 */
export function initWeather() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                fetchWeatherData(position.coords.latitude, position.coords.longitude);
            },
            (error) => {
                console.warn('Geolocation error, using default location:', error);
                fetchWeatherData(DEFAULT_LATITUDE, DEFAULT_LONGITUDE);
            },
            {
                timeout: 5000,
                enableHighAccuracy: false
            }
        );
    } else {
        fetchWeatherData(DEFAULT_LATITUDE, DEFAULT_LONGITUDE);
    }
}
