package proxy

import (
	"fmt"
	"sync"
	"time"
	"proxy-service/config"
)

type Proxy struct {
	ID       string    `json:"id"`
	Host     string    `json:"host"`
	Port     int       `json:"port"`
	Username string    `json:"username,omitempty"`
	Password string    `json:"password,omitempty"`
	Protocol string    `json:"protocol"`
	LastUsed time.Time `json:"last_used"`
	Failures int       `json:"failures"`
	Active   bool      `json:"active"`
}

type Manager struct {
	proxies    []*Proxy
	mu         sync.RWMutex
	config     *config.Config
	currentIdx int
}

func NewManager(cfg *config.Config) *Manager {
	return &Manager{
		proxies:    make([]*Proxy, 0),
		config:     cfg,
		currentIdx: 0,
	}
}

func (m *Manager) AddProxy(proxy *Proxy) error {
	m.mu.Lock()
	defer m.mu.Unlock()

	proxy.ID = generateID()
	proxy.LastUsed = time.Now()
	proxy.Active = true
	proxy.Failures = 0

	m.proxies = append(m.proxies, proxy)
	return nil
}

func (m *Manager) GetProxy() (*Proxy, error) {
	m.mu.Lock()
	defer m.mu.Unlock()

	if len(m.proxies) == 0 {
		return nil, fmt.Errorf("no proxies available")
	}

	activeProxies := m.getActiveProxies()
	if len(activeProxies) == 0 {
		return nil, fmt.Errorf("no active proxies available")
	}

	proxy := m.selectBestProxy(activeProxies)
	proxy.LastUsed = time.Now()

	return proxy, nil
}

func (m *Manager) RotateProxy() (*Proxy, error) {
	m.mu.Lock()
	defer m.mu.Unlock()

	if len(m.proxies) == 0 {
		return nil, fmt.Errorf("no proxies available")
	}

	activeProxies := m.getActiveProxies()
	if len(activeProxies) == 0 {
		return nil, fmt.Errorf("no active proxies available")
	}

	m.currentIdx = (m.currentIdx + 1) % len(activeProxies)
	proxy := activeProxies[m.currentIdx]
	proxy.LastUsed = time.Now()

	return proxy, nil
}

func (m *Manager) RemoveProxy(id string) error {
	m.mu.Lock()
	defer m.mu.Unlock()

	for i, proxy := range m.proxies {
		if proxy.ID == id {
			m.proxies = append(m.proxies[:i], m.proxies[i+1:]...)
			return nil
		}
	}

	return fmt.Errorf("proxy not found")
}

func (m *Manager) ListProxies() []*Proxy {
	m.mu.RLock()
	defer m.mu.RUnlock()

	result := make([]*Proxy, len(m.proxies))
	copy(result, m.proxies)
	return result
}

func (m *Manager) MarkProxyFailed(id string) {
	m.mu.Lock()
	defer m.mu.Unlock()

	for _, proxy := range m.proxies {
		if proxy.ID == id {
			proxy.Failures++
			if proxy.Failures >= m.config.MaxRetries {
				proxy.Active = false
			}
			break
		}
	}
}

func (m *Manager) GetStats() map[string]interface{} {
	m.mu.RLock()
	defer m.mu.RUnlock()

	total := len(m.proxies)
	active := 0
	failed := 0

	for _, proxy := range m.proxies {
		if proxy.Active {
			active++
		} else {
			failed++
		}
	}

	return map[string]interface{}{
		"total_proxies":  total,
		"active_proxies": active,
		"failed_proxies": failed,
		"current_index":  m.currentIdx,
	}
}

func (m *Manager) getActiveProxies() []*Proxy {
	var active []*Proxy
	for _, proxy := range m.proxies {
		if proxy.Active {
			active = append(active, proxy)
		}
	}
	return active
}

func (m *Manager) selectBestProxy(proxies []*Proxy) *Proxy {
	if len(proxies) == 0 {
		return nil
	}

	bestProxy := proxies[0]
	bestScore := m.calculateScore(bestProxy)

	for _, proxy := range proxies[1:] {
		score := m.calculateScore(proxy)
		if score > bestScore {
			bestScore = score
			bestProxy = proxy
		}
	}

	return bestProxy
}

func (m *Manager) calculateScore(proxy *Proxy) float64 {
	timeSinceLastUse := time.Since(proxy.LastUsed).Seconds()
	failurePenalty := float64(proxy.Failures) * 10.0

	return timeSinceLastUse - failurePenalty
}

func generateID() string {
	return fmt.Sprintf("proxy_%d", time.Now().UnixNano())
}
