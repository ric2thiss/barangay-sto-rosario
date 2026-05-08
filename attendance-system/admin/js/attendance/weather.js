/**
 * Weather Module
 * Handles weather API integration and display
 */
export class Weather {
    constructor(defaultLatitude = 8.95, defaultLongitude = 125.53) {
        this.defaultLatitude = defaultLatitude;
        this.defaultLongitude = defaultLongitude;
        this.weatherIconEl = document.getElementById('weather-icon');
        this.weatherConditionEl = document.getElementById('weather-condition');
        this.weatherTemperatureEl = document.getElementById('weather-temperature');
        this.weatherDetailsEl = document.getElementById('weather-details');
        this.refreshInterval = null;
    }

    /**
     * Get weather icon based on weather code
     */
    getWeatherIcon(weatherCode) {
        if (!this.weatherIconEl) return;
        
        // Clear out existing content
        this.weatherIconEl.innerHTML = '';
        
        let svgContent = '';
        
        // WMO Weather Interpretation Codes
        if (weatherCode === 0) {
            // Clear sky - Sun icon
            svgContent = `<svg class="w-6 h-6 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>`;
        } else if (weatherCode >= 1 && weatherCode <= 3) {
            // Partly cloudy - Cloud icon (gray)
            svgContent = `<svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"></path></svg>`;
        } else if (weatherCode >= 45 && weatherCode <= 48) {
            // Fog - Cloud icon (darker gray)
            svgContent = `<svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"></path></svg>`;
        } else if ((weatherCode >= 51 && weatherCode <= 67) || (weatherCode >= 80 && weatherCode <= 82)) {
            // Rain - Cloud with rain drops icon
            svgContent = `<svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 18v-2m4 2v-4m4 4v-3"></path></svg>`;
        } else if ((weatherCode >= 71 && weatherCode <= 77) || (weatherCode >= 85 && weatherCode <= 86)) {
            // Snow - Cloud with snowflake icon
            svgContent = `<svg class="w-6 h-6 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M15 18h.01M9 18h.01M9 15h.01M12 15h.01M15 15h.01"></path></svg>`;
        } else if (weatherCode >= 95 && weatherCode <= 99) {
            // Thunderstorm - Lightning bolt icon
            svgContent = `<svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>`;
        } else {
            // Default - Cloud icon
            svgContent = `<svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"></path></svg>`;
        }
        
        this.weatherIconEl.innerHTML = svgContent;
    }

    /**
     * Get weather condition text
     */
    getWeatherCondition(weatherCode) {
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
     */
    async fetchWeatherData(latitude, longitude) {
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
                
                // Update weather icon
                this.getWeatherIcon(weatherCode);
                
                // Update weather information display
                if (this.weatherConditionEl) {
                    this.weatherConditionEl.textContent = this.getWeatherCondition(weatherCode);
                }
                
                if (this.weatherTemperatureEl) {
                    this.weatherTemperatureEl.textContent = `${temperature}°C`;
                }
                
                if (this.weatherDetailsEl) {
                    this.weatherDetailsEl.textContent = `Humidity: ${humidity}% | Wind: ${windSpeed} km/h`;
                }
            }
        } catch (error) {
            console.error('Error fetching weather data:', error);
            // Show error state
            if (this.weatherConditionEl) this.weatherConditionEl.textContent = 'Unable to load weather';
            if (this.weatherTemperatureEl) this.weatherTemperatureEl.textContent = '-';
            if (this.weatherDetailsEl) this.weatherDetailsEl.textContent = 'Please try again later';
        }
    }

    /**
     * Initialize weather with user location
     */
    init() {
        // Try to get user's location
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    // Use user's actual location
                    this.fetchWeatherData(position.coords.latitude, position.coords.longitude);
                },
                (error) => {
                    // Fallback to default location if geolocation fails
                    console.warn('Geolocation error, using default location:', error);
                    this.fetchWeatherData(this.defaultLatitude, this.defaultLongitude);
                },
                {
                    timeout: 5000,
                    enableHighAccuracy: false
                }
            );
        } else {
            // Browser doesn't support geolocation, use default location
            this.fetchWeatherData(this.defaultLatitude, this.defaultLongitude);
        }

        // Refresh weather every 30 minutes
        this.refreshInterval = setInterval(() => {
            this.init();
        }, 30 * 60 * 1000);
    }

    /**
     * Stop weather updates
     */
    stop() {
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
            this.refreshInterval = null;
        }
    }
}
