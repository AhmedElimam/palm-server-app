package config

import (
	"net/url"
	"os"
	"strconv"
)

type Config struct {
	Port          string
	BaseURL       string
	RedisURL      string
	ProxyTimeout  int
	MaxRetries    int
	RotationDelay int
}

func Load() *Config {
	baseURL := getEnv("BASE_URL", "http://localhost:8080")
	port := getEnv("PORT", "")

	if port == "" {
		if parsedURL, err := url.Parse(baseURL); err == nil {
			if parsedURL.Port() != "" {
				port = parsedURL.Port()
			} else {
				port = "8080"
			}
		} else {
			port = "8080"
		}
	}

	return &Config{
		Port:          port,
		BaseURL:       baseURL,
		RedisURL:      getEnv("REDIS_URL", "redis://localhost:6379"),
		ProxyTimeout:  getEnvAsInt("PROXY_TIMEOUT", 30),
		MaxRetries:    getEnvAsInt("MAX_RETRIES", 3),
		RotationDelay: getEnvAsInt("ROTATION_DELAY", 5),
	}
}

func getEnv(key, defaultValue string) string {
	if value := os.Getenv(key); value != "" {
		return value
	}
	return defaultValue
}

func getEnvAsInt(key string, defaultValue int) int {
	if value := os.Getenv(key); value != "" {
		if intValue, err := strconv.Atoi(value); err == nil {
			return intValue
		}
	}
	return defaultValue
}
